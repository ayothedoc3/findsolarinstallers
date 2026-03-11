from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    # App
    app_name: str = "SolarListings API"
    debug: bool = False
    secret_key: str = "change-me-in-production"
    encryption_key: str = "change-me-in-production"

    # Database
    database_url: str = "postgresql+asyncpg://solar_user:Z7nmNDVHzTdhaUU9U9Li4w3U@db:5432/solarlisting"

    # Redis
    redis_url: str = "redis://redis:6379/0"

    # JWT
    access_token_expire_minutes: int = 30
    refresh_token_expire_days: int = 7
    jwt_algorithm: str = "HS256"

    # Admin
    admin_email: str = "admin@findsolarinstallers.xyz"
    admin_password: str = "changeme123"

    # Stripe
    stripe_secret_key: str = ""
    stripe_webhook_secret: str = ""
    lead_price_cents: int = 1999  # $19.99 per lead

    # SMTP (outreach emails)
    smtp_host: str = ""
    smtp_port: int = 587
    smtp_user: str = ""
    smtp_password: str = ""
    smtp_from_email: str = "info@findsolarinstallers.xyz"
    smtp_from_name: str = "Find Solar Installers"

    # Pipeline
    monthly_credit_budget: float = 500.0

    model_config = {"env_file": ".env", "extra": "ignore"}


settings = Settings()
