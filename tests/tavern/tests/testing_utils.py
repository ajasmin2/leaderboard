import os
import requests
import uuid
import json
from box import Box
from filecache import filecache
from pykwalify.core import Core
from pykwalify.errors import SchemaError


def validate_ranks(response, expected_ranks):
    # Validate that users and ranks match according theirs values previously added
    assert response.json().get("leaderboard_ranks") == expected_ranks


def create_random_leaderboard_name():
    sufix = name = str(uuid.uuid4())[:8]
    return Box({"name": f"random_leaderboard_{sufix}"})

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
