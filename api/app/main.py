import json
import logging
from time import time

from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Optional

from request_parser import process_request

# ------------------------------------------------------
# Rate limit configuration
# ------------------------------------------------------

RATE_LIMIT = 3
RATE_INTERVAL = 60
rate_storage = {}
WHITELIST = ["dashboard.api.ventureout.cz", "quantum.api.ventureout.cz"]


# ------------------------------------------------------
# Logging configuration
# ------------------------------------------------------

logger = logging.getLogger("quantum_api")
logger.setLevel(logging.INFO)

handler = logging.FileHandler("/var/log/quantum-api/requests.log")
formatter = logging.Formatter("%(asctime)s %(message)s")
handler.setFormatter(formatter)
logger.addHandler(handler)


# ------------------------------------------------------
# FastAPI application
# ------------------------------------------------------

app = FastAPI(title="Quantum Random API")

# ------------------------------------------------------
# CORS MIDDLEWARE (musí být před vším)
# ------------------------------------------------------

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "https://dashboard.api.ventureout.cz",
        "https://quantum.api.ventureout.cz",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ------------------------------------------------------
# Rate-limit middleware
# ------------------------------------------------------

@app.middleware("http")
async def rate_limit(request: Request, call_next):

    # whitelist domain?
    origin = request.headers.get("origin", "")
    if any(w in origin for w in WHITELIST):
        return await call_next(request)

    client_ip = request.client.host
    now = time()

    timestamps = rate_storage.get(client_ip, [])
    timestamps = [t for t in timestamps if now - t < RATE_INTERVAL]

    if len(timestamps) >= RATE_LIMIT:
        return JSONResponse(
            status_code=429,
            content={"error": "Rate limit exceeded. Try again later."}
        )

    timestamps.append(now)
    rate_storage[client_ip] = timestamps

    return await call_next(request)


# ------------------------------------------------------
# Logging middleware
# ------------------------------------------------------

@app.middleware("http")
async def log_requests(request: Request, call_next):

    try:
        body_bytes = await request.body()
        body_text = body_bytes.decode("utf-8") if body_bytes else ""
    except Exception:
        body_text = "<unreadable>"

    client_ip = request.client.host
    method = request.method
    path = request.url.path
    ua = request.headers.get("user-agent", "-")

    logger.info(f"REQUEST ip={client_ip} method={method} path={path} ua='{ua}' body={body_text}")

    response = await call_next(request)

    try:
        if isinstance(response, JSONResponse):
            response_body = response.body.decode("utf-8")
        else:
            response_body = "<non-json>"
    except Exception:
        response_body = "<error-reading-response>"

    logger.info(f"RESPONSE ip={client_ip} path={path} body={response_body}")

    return response


# ------------------------------------------------------
# Models
# ------------------------------------------------------

class RandomConfig(BaseModel):
    type: str
    range: Optional[List[int]] = None
    alphabet: Optional[str] = None
    count: int
    unique: bool = False


class RandomTask(BaseModel):
    random: RandomConfig


class RequestModel(BaseModel):
    request: List[RandomTask]


# ------------------------------------------------------
# API endpoints
# ------------------------------------------------------

@app.post("/random")
def generate_random(data: RequestModel):
    req_list = [{"random": task.random.dict()} for task in data.request]
    result = process_request(req_list)
    return {"result": result}


@app.get("/")
def root():
    return {"message": "Quantum Random API running"}

