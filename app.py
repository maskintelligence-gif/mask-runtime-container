from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Optional, List
import uvicorn
from datetime import datetime
import os

app = FastAPI(title="Python FastAPI Service")

# In-memory database
items_db = {}
items_counter = 0

# Models
class Item(BaseModel):
    name: str
    price: float
    description: Optional[str] = None

class ItemResponse(Item):
    id: int
    created_at: str

# Root endpoint
@app.get("/")
async def root():
    return {
        "service": "Python FastAPI",
        "status": "running",
        "timestamp": datetime.now().isoformat(),
        "endpoints": {
            "GET /health": "Health check",
            "GET /items": "List all items",
            "POST /items": "Create new item",
            "GET /items/{id}": "Get item by ID",
            "PUT /items/{id}": "Update item",
            "DELETE /items/{id}": "Delete item"
        }
    }

# Health check
@app.get("/health")
async def health():
    return {
        "status": "healthy",
        "service": "python",
        "timestamp": datetime.now().isoformat()
    }

# Get all items
@app.get("/items", response_model=List[ItemResponse])
async def get_items():
    return list(items_db.values())

# Create new item
@app.post("/items", response_model=ItemResponse)
async def create_item(item: Item):
    global items_counter
    items_counter += 1
    new_item = {
        "id": items_counter,
        "created_at": datetime.now().isoformat(),
        **item.dict()
    }
    items_db[items_counter] = new_item
    return new_item

# Get item by ID
@app.get("/items/{item_id}", response_model=ItemResponse)
async def get_item(item_id: int):
    if item_id not in items_db:
        raise HTTPException(status_code=404, detail="Item not found")
    return items_db[item_id]

# Update item
@app.put("/items/{item_id}", response_model=ItemResponse)
async def update_item(item_id: int, item: Item):
    if item_id not in items_db:
        raise HTTPException(status_code=404, detail="Item not found")
    updated_item = {
        **items_db[item_id],
        **item.dict(),
        "updated_at": datetime.now().isoformat()
    }
    items_db[item_id] = updated_item
    return updated_item

# Delete item
@app.delete("/items/{item_id}")
async def delete_item(item_id: int):
    if item_id not in items_db:
        raise HTTPException(status_code=404, detail="Item not found")
    del items_db[item_id]
    return {"message": "Item deleted successfully"}

if __name__ == "__main__":
    port = int(os.getenv("PORT", 8000))
    uvicorn.run(app, host="0.0.0.0", port=port)
