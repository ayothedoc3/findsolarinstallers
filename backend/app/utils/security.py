import base64
import hashlib
import logging
from datetime import datetime, timedelta, timezone

import bcrypt
from cryptography.fernet import Fernet
from jose import jwt

from app.config import settings

logger = logging.getLogger(__name__)
_fernet_client: Fernet | None = None


def _get_fernet() -> Fernet:
    """Return a usable Fernet instance from either a real Fernet key or a passphrase."""
    global _fernet_client
    if _fernet_client is not None:
        return _fernet_client

    raw_key = (settings.encryption_key or "").strip()

    try:
        _fernet_client = Fernet(raw_key.encode())
        return _fernet_client
    except Exception:
        # Allow human-readable passphrases/placeholders in ENCRYPTION_KEY by deriving
        # a stable Fernet key from the configured string.
        seed = raw_key or settings.secret_key or "change-me-in-production"
        derived_key = base64.urlsafe_b64encode(hashlib.sha256(seed.encode()).digest())
        logger.warning("ENCRYPTION_KEY is not a valid Fernet key; using a derived key from the configured value.")
        _fernet_client = Fernet(derived_key)
        return _fernet_client


def hash_password(password: str) -> str:
    return bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()


def verify_password(plain_password: str, hashed_password: str) -> bool:
    return bcrypt.checkpw(plain_password.encode(), hashed_password.encode())


def create_access_token(data: dict) -> str:
    to_encode = data.copy()
    expire = datetime.now(timezone.utc) + timedelta(minutes=settings.access_token_expire_minutes)
    to_encode.update({"exp": expire, "type": "access"})
    return jwt.encode(to_encode, settings.secret_key, algorithm=settings.jwt_algorithm)


def create_refresh_token(data: dict) -> str:
    to_encode = data.copy()
    expire = datetime.now(timezone.utc) + timedelta(days=settings.refresh_token_expire_days)
    to_encode.update({"exp": expire, "type": "refresh"})
    return jwt.encode(to_encode, settings.secret_key, algorithm=settings.jwt_algorithm)


def decode_token(token: str) -> dict:
    return jwt.decode(token, settings.secret_key, algorithms=[settings.jwt_algorithm])


def encrypt_api_key(key: str) -> str:
    f = _get_fernet()
    return f.encrypt(key.encode()).decode()


def decrypt_api_key(encrypted_key: str) -> str:
    f = _get_fernet()
    return f.decrypt(encrypted_key.encode()).decode()
