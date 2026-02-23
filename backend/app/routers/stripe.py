import logging
from datetime import datetime, timezone

import stripe
from fastapi import APIRouter, Depends, HTTPException, Request
from pydantic import BaseModel
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.config import settings
from app.database import get_db
from app.models.contact_request import ContactRequest
from app.models.lead_purchase import LeadPurchase
from app.models.listing import Listing
from app.models.user import User
from app.routers.auth import get_current_user

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/api/stripe", tags=["stripe"])


class CheckoutRequest(BaseModel):
    lead_id: int


@router.post("/checkout")
async def create_checkout(
    data: CheckoutRequest,
    user: User = Depends(get_current_user),
    db: AsyncSession = Depends(get_db),
):
    if not settings.stripe_secret_key:
        raise HTTPException(status_code=503, detail="Stripe is not configured")

    # Verify lead exists
    result = await db.execute(
        select(ContactRequest).where(ContactRequest.id == data.lead_id)
    )
    lead = result.scalar_one_or_none()
    if not lead:
        raise HTTPException(status_code=404, detail="Lead not found")

    # Verify lead belongs to one of the user's listings
    listing_result = await db.execute(
        select(Listing).where(Listing.id == lead.listing_id, Listing.owner_id == user.id)
    )
    if not listing_result.scalar_one_or_none():
        raise HTTPException(status_code=403, detail="This lead does not belong to your listing")

    # Check if already purchased
    existing = await db.execute(
        select(LeadPurchase).where(
            LeadPurchase.user_id == user.id,
            LeadPurchase.contact_request_id == data.lead_id,
            LeadPurchase.status == "completed",
        )
    )
    if existing.scalar_one_or_none():
        raise HTTPException(status_code=400, detail="Lead already unlocked")

    # Create Stripe Checkout session
    stripe.api_key = settings.stripe_secret_key
    price_cents = settings.lead_price_cents

    session = stripe.checkout.Session.create(
        mode="payment",
        line_items=[{
            "price_data": {
                "currency": "usd",
                "unit_amount": price_cents,
                "product_data": {
                    "name": f"Lead Unlock — {lead.name} ({lead.project_type or 'Solar Project'})",
                },
            },
            "quantity": 1,
        }],
        metadata={
            "lead_id": str(data.lead_id),
            "user_id": str(user.id),
        },
        success_url=f"{_get_base_url()}/dashboard?payment=success&lead_id={data.lead_id}",
        cancel_url=f"{_get_base_url()}/dashboard?payment=cancelled",
    )

    # Store pending purchase
    purchase = LeadPurchase(
        user_id=user.id,
        contact_request_id=data.lead_id,
        stripe_session_id=session.id,
        amount_cents=price_cents,
        status="pending",
    )
    db.add(purchase)
    await db.commit()

    return {"checkout_url": session.url}


@router.post("/webhook")
async def stripe_webhook(request: Request, db: AsyncSession = Depends(get_db)):
    payload = await request.body()
    sig_header = request.headers.get("stripe-signature", "")

    if settings.stripe_webhook_secret:
        try:
            event = stripe.Webhook.construct_event(
                payload, sig_header, settings.stripe_webhook_secret
            )
        except stripe.error.SignatureVerificationError:
            raise HTTPException(status_code=400, detail="Invalid signature")
    else:
        import json
        event = json.loads(payload)

    if event.get("type") == "checkout.session.completed":
        session = event["data"]["object"]
        session_id = session["id"]

        result = await db.execute(
            select(LeadPurchase).where(LeadPurchase.stripe_session_id == session_id)
        )
        purchase = result.scalar_one_or_none()
        if purchase:
            purchase.status = "completed"
            purchase.completed_at = datetime.now(timezone.utc)
            purchase.stripe_payment_intent = session.get("payment_intent")
            await db.commit()
            logger.info(f"Lead purchase completed: user={purchase.user_id}, lead={purchase.contact_request_id}")

    return {"received": True}


def _get_base_url() -> str:
    """Return the frontend base URL for Stripe redirects."""
    return "https://findsolarinstallers.xyz"
