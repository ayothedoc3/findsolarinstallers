import logging
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

from app.config import settings

logger = logging.getLogger(__name__)

OUTREACH_SUBJECT = "Your solar company is listed on FindSolarInstallers.xyz"


def send_email(to_email: str, subject: str, html_body: str, text_body: str | None = None) -> bool:
    """Send a single email via SMTP. Returns True on success."""
    if not settings.smtp_host:
        logger.warning("SMTP not configured - skipping email to %s", to_email)
        return False

    msg = MIMEMultipart("alternative")
    msg["From"] = f"{settings.smtp_from_name} <{settings.smtp_from_email}>"
    msg["To"] = to_email
    msg["Subject"] = subject

    if text_body:
        msg.attach(MIMEText(text_body, "plain"))
    msg.attach(MIMEText(html_body, "html"))

    try:
        with smtplib.SMTP(settings.smtp_host, settings.smtp_port, timeout=15) as server:
            server.ehlo()
            if settings.smtp_port != 25:
                server.starttls()
                server.ehlo()
            if settings.smtp_user:
                server.login(settings.smtp_user, settings.smtp_password)
            server.sendmail(settings.smtp_from_email, to_email, msg.as_string())
        logger.info("Email sent to %s: %s", to_email, subject)
        return True
    except Exception:
        logger.exception("Failed to send email to %s", to_email)
        return False


def render_outreach_email(
    business_name: str,
    listing_url: str,
    market_state: str,
    market_state_code: str | None = None,
    featured_example_url: str | None = None,
) -> tuple[str, str]:
    """Return (html_body, text_body) for the installer outreach email."""
    search_label = market_state_code or market_state
    featured_line_html = ""
    featured_line_text = ""
    if featured_example_url:
        featured_line_html = (
            f'<p>See what a featured profile looks like: '
            f'<a href="{featured_example_url}">{featured_example_url}</a></p>'
        )
        featured_line_text = f"\nSee what a featured profile looks like: {featured_example_url}"

    text = f"""Hi,

{business_name} has a free profile on FindSolarInstallers.xyz - {market_state}'s solar installer directory.

Right now your listing does not show your phone number or website. Homeowners who find you can only submit a quote request.

Your current listing: {listing_url}
{featured_line_text}
For $99/month, your profile gets:
- Direct phone and website visible to customers
- Featured badge and top placement in {search_label} search results
- Verified business status
- Monthly performance summary

Interested? Reply to this email and we will set it up in 5 minutes.

Find Solar Installers
{settings.smtp_from_email}"""

    html = f"""<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto; color: #1a1a1a; line-height: 1.6;">
  <p>Hi,</p>
  <p><strong>{business_name}</strong> has a free profile on <a href="https://findsolarinstallers.xyz">FindSolarInstallers.xyz</a> - {market_state}'s solar installer directory.</p>
  <p>Right now your listing does not show your phone number or website. Homeowners who find you can only submit a quote request.</p>
  <p>Your current listing: <a href="{listing_url}">{listing_url}</a></p>
  {featured_line_html}
  <p>For <strong>$99/month</strong>, your profile gets:</p>
  <ul>
    <li>Direct phone and website visible to customers</li>
    <li>Featured badge and top placement in {search_label} search results</li>
    <li>Verified business status</li>
    <li>Monthly performance summary</li>
  </ul>
  <p>Interested? <strong>Reply to this email</strong> and we will set it up in 5 minutes.</p>
  <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 24px 0;" />
  <p style="color: #666; font-size: 14px;">Find Solar Installers<br/>{settings.smtp_from_email}</p>
</div>"""

    return html, text
