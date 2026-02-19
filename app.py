from fastapi import FastAPI, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
import uvicorn
import asyncio
from datetime import datetime
import json

app = FastAPI(title="Multi-Runtime API")

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Models
class Item(BaseModel):
    name: str
    price: float
    description: Optional[str] = None

class User(BaseModel):
    username: str
    email: str
    full_name: Optional[str] = None

# In-memory storage
items_db = {}
users_db = {}

# Routes
@app.get("/")
async def root():
    return {
        "service": "Python FastAPI",
        "version": "1.0.0",
        "status": "running",
        "timestamp": datetime.now().isoformat()
    }

@app.get("/health")
async def health():
    return {"status": "healthy", "service": "python"}

@app.get("/items")
async def get_items():
    return list(items_db.values())

@app.get("/items/{item_id}")
async def get_item(item_id: str):
    if item_id not in items_db:
        raise HTTPException(status_code=404, detail="Item not found")
    return items_db[item_id]

@app.post("/items")
async def create_item(item: Item):
    item_id = str(len(items_db) + 1)
    items_db[item_id] = item.dict()
    return {"id": item_id, **item.dict()}

@app.put("/items/{item_id}")
async def update_item(item_id: str, item: Item):
    if item_id not in items_db:
        raise HTTPException(status_code=404, detail="Item not found")
    items_db[item_id] = item.dict()
    return items_db[item_id]

@app.delete("/items/{item_id}")
async def delete_item(item_id: str):
    if item_id not in items_db:
        raise HTTPException(status_code=404, detail="Item not found")
    del items_db[item_id]
    return {"message": "Item deleted"}

@app.post("/users")
async def create_user(user: User):
    users_db[user.username] = user.dict()
    return user

# Background task example
@app.post("/ process/{item_id}")
async def process_item(item_id: str, background_tasks: BackgroundTasks):
    if item_id not in items_db:
        raise HTTPException(status_code=404, detail="Item not found")
    
    background_tasks.add_task(process_in_background, item_id)
    return {"message": "Processing started"}

async def process_in_background(item_id: str):
    await asyncio.sleep(5)  # Simulate work
    print(f"Processed item {item_id}")
    items_db[item_id]["processed"] = True

# WebSocket endpoint would be handled by Node.js
# Python focuses on REST API

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)
