import os
import logging
from typing import Optional
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from PIL import Image
import torch
from transformers import BlipProcessor, BlipForConditionalGeneration
from transformers import CLIPProcessor, CLIPModel, AutoProcessor, AutoModel
import numpy as np

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="Avinash-EYE AI Service")

# Model cache
captioning_models = {}
embedding_models = {}

# ====================================================================
# Request/Response Models
# ====================================================================

class ImageAnalysisRequest(BaseModel):
    image_path: str
    captioning_model: Optional[str] = "Salesforce/blip-image-captioning-large"
    embedding_model: Optional[str] = "laion/CLIP-ViT-B-32-laion2B-s34B-b79K"
    face_detection_enabled: Optional[bool] = True

class TextEmbeddingRequest(BaseModel):
    query: str
    embedding_model: Optional[str] = "laion/CLIP-ViT-B-32-laion2B-s34B-b79K"

# ====================================================================
# Model Loading Functions
# ====================================================================

def load_captioning_model(model_name: str):
    """Load and cache a captioning model."""
    if model_name in captioning_models:
        return captioning_models[model_name]
    
    logger.info(f"Loading captioning model: {model_name}")
    try:
        if "blip" in model_name.lower():
            processor = BlipProcessor.from_pretrained(model_name)
            model = BlipForConditionalGeneration.from_pretrained(model_name)
        elif "vit-gpt2" in model_name.lower():
            processor = AutoProcessor.from_pretrained(model_name)
            model = AutoModel.from_pretrained(model_name)
        else:
            # Default to BLIP
            processor = BlipProcessor.from_pretrained(model_name)
            model = BlipForConditionalGeneration.from_pretrained(model_name)
        
        device = "cuda" if torch.cuda.is_available() else "cpu"
        model = model.to(device)
        model.eval()
        
        captioning_models[model_name] = {
            'processor': processor,
            'model': model,
            'device': device
        }
        
        logger.info(f"Captioning model loaded successfully: {model_name}")
        return captioning_models[model_name]
        
    except Exception as e:
        logger.error(f"Failed to load captioning model {model_name}: {e}")
        raise HTTPException(status_code=500, detail=f"Failed to load captioning model: {str(e)}")

def load_embedding_model(model_name: str):
    """Load and cache an embedding model."""
    if model_name in embedding_models:
        return embedding_models[model_name]
    
    logger.info(f"Loading embedding model: {model_name}")
    try:
        if "clip" in model_name.lower():
            processor = CLIPProcessor.from_pretrained(model_name)
            model = CLIPModel.from_pretrained(model_name)
        elif "dinov2" in model_name.lower():
            processor = AutoProcessor.from_pretrained(model_name)
            model = AutoModel.from_pretrained(model_name)
        else:
            # Default to CLIP
            processor = CLIPProcessor.from_pretrained(model_name)
            model = CLIPModel.from_pretrained(model_name)
        
        device = "cuda" if torch.cuda.is_available() else "cpu"
        model = model.to(device)
        model.eval()
        
        embedding_models[model_name] = {
            'processor': processor,
            'model': model,
            'device': device
        }
        
        logger.info(f"Embedding model loaded successfully: {model_name}")
        return embedding_models[model_name]
        
    except Exception as e:
        logger.error(f"Failed to load embedding model {model_name}: {e}")
        raise HTTPException(status_code=500, detail=f"Failed to load embedding model: {str(e)}")

# ====================================================================
# Helper Functions
# ====================================================================

async def generate_caption(image: Image.Image, model_name: str) -> str:
    """Generate caption using specified model."""
    model_data = load_captioning_model(model_name)
    processor = model_data['processor']
    model = model_data['model']
    device = model_data['device']
    
    inputs = processor(images=image, return_tensors="pt").to(device)
    
    with torch.no_grad():
        if "blip" in model_name.lower():
            outputs = model.generate(
                **inputs,
                max_length=75,
                num_beams=5,
                temperature=1.0
            )
        else:
            outputs = model.generate(**inputs)
    
    caption = processor.decode(outputs[0], skip_special_tokens=True)
    return caption.strip()

async def generate_embedding(image: Image.Image, model_name: str) -> np.ndarray:
    """Generate embedding using specified model."""
    model_data = load_embedding_model(model_name)
    processor = model_data['processor']
    model = model_data['model']
    device = model_data['device']
    
    # Process image
    inputs = processor(images=image, return_tensors="pt").to(device)
    
    with torch.no_grad():
        if "clip" in model_name.lower():
            image_features = model.get_image_features(**inputs)
        else:
            outputs = model(**inputs)
            image_features = outputs.last_hidden_state.mean(dim=1)
    
    # Normalize
    embedding = image_features / image_features.norm(dim=-1, keepdim=True)
    embedding = embedding.cpu().numpy()[0]
    
    # Ensure 512 dimensions (pad or truncate if necessary)
    if len(embedding) < 512:
        embedding = np.pad(embedding, (0, 512 - len(embedding)))
    elif len(embedding) > 512:
        embedding = embedding[:512]
    
    return embedding

async def generate_text_embedding(text: str, model_name: str) -> np.ndarray:
    """Generate text embedding using specified model."""
    model_data = load_embedding_model(model_name)
    processor = model_data['processor']
    model = model_data['model']
    device = model_data['device']
    
    # Process text
    inputs = processor(text=[text], return_tensors="pt", padding=True).to(device)
    
    with torch.no_grad():
        if "clip" in model_name.lower():
            text_features = model.get_text_features(**inputs)
        else:
            outputs = model(**inputs)
            text_features = outputs.last_hidden_state.mean(dim=1)
    
    # Normalize
    embedding = text_features / text_features.norm(dim=-1, keepdim=True)
    embedding = embedding.cpu().numpy()[0]
    
    # Ensure 512 dimensions
    if len(embedding) < 512:
        embedding = np.pad(embedding, (0, 512 - len(embedding)))
    elif len(embedding) > 512:
        embedding = embedding[:512]
    
    return embedding

# ====================================================================
# API Endpoints
# ====================================================================

@app.get("/health")
async def health_check():
    """Health check endpoint."""
    return {
        "status": "healthy",
        "loaded_captioning_models": list(captioning_models.keys()),
        "loaded_embedding_models": list(embedding_models.keys()),
        "device": "cuda" if torch.cuda.is_available() else "cpu"
    }

@app.post("/analyze")
async def analyze_image(request: ImageAnalysisRequest):
    """
    Analyze an image and return description and embedding.
    """
    try:
        # Load image
        if not os.path.exists(request.image_path):
            raise HTTPException(status_code=404, detail=f"Image not found: {request.image_path}")
        
        image = Image.open(request.image_path).convert('RGB')
        logger.info(f"Processing image: {request.image_path}")
        
        # Generate caption
        caption = await generate_caption(image, request.captioning_model)
        logger.info(f"Generated caption using {request.captioning_model}")
        
        # Generate embedding
        embedding = await generate_embedding(image, request.embedding_model)
        logger.info(f"Generated embedding using {request.embedding_model}")
        
        # Face detection (if enabled)
        face_count = 0
        face_encodings = []
        if request.face_detection_enabled:
            try:
                import face_recognition
                img_array = np.array(image)
                face_locations = face_recognition.face_locations(img_array)
                face_encodings_raw = face_recognition.face_encodings(img_array, face_locations)
                face_count = len(face_locations)
                face_encodings = [encoding.tolist() for encoding in face_encodings_raw]
                logger.info(f"Detected {face_count} faces")
            except ImportError:
                logger.warning("face_recognition not installed, skipping face detection")
            except Exception as e:
                logger.warning(f"Face detection failed: {e}")
        
        return {
            "description": caption,
            "embedding": embedding.tolist(),
            "face_count": face_count,
            "face_encodings": face_encodings,
            "models_used": {
                "captioning": request.captioning_model,
                "embedding": request.embedding_model
            }
        }
        
    except Exception as e:
        logger.error(f"Error analyzing image: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/embed-text")
async def embed_text(request: TextEmbeddingRequest):
    """
    Generate embedding for text query.
    """
    try:
        logger.info(f"Embedding text query: {request.query[:50]}...")
        
        # Generate embedding
        embedding = await generate_text_embedding(request.query, request.embedding_model)
        
        return {
            "embedding": embedding.tolist(),
            "model_used": request.embedding_model
        }
        
    except Exception as e:
        logger.error(f"Error embedding text: {e}")
        raise HTTPException(status_code=500, detail=str(e))

# ====================================================================
# Startup
# ====================================================================

if __name__ == "__main__":
    import uvicorn
    
    # Pre-load default models
    logger.info("Pre-loading default models...")
    try:
        load_captioning_model("Salesforce/blip-image-captioning-large")
        load_embedding_model("laion/CLIP-ViT-B-32-laion2B-s34B-b79K")
        logger.info("Default models loaded successfully")
    except Exception as e:
        logger.warning(f"Failed to pre-load default models: {e}")
    
    uvicorn.run(app, host="0.0.0.0", port=8000)

