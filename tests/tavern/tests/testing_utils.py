import os
import requests
from box import Box
from filecache import filecache
from pykwalify.core import Core
from pykwalify.errors import SchemaError
import json


@filecache(60)
def authorization_password_user():
    r = _user_authorization_password(
        email=os.environ.get("TEST_USER_EMAIL", None),
        password=os.environ.get("TEST_USER_PASSWORD", None),
    )

    if r.status_code != 200:
        return Box({"Authorization": "TOKEN_NOT_FOUND"})

    return Box({"Authorization": f"Bearer {r.json().get('token')}"})


def _user_authorization_password(email: str, password: str):
    url = f"{os.environ.get('AUTH_URL', None)}"
    return requests.post(
        url,
        json={
            "email": email,
            "password": password,
        },
    )
