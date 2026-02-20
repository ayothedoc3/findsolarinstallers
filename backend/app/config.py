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

    # Pipeline
    monthly_credit_budget: float = 500.0

    model_config = {"env_file": ".env", "extra": "ignore"}


settings = Settings()
