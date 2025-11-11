"""
Enhanced FastAPI service with Ollama, face detection, and detailed analysis.
"""

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from transformers import BlipProcessor, BlipForConditionalGeneration
from transformers import CLIPProcessor, CLIPModel
import torch
from PIL import Image
import numpy as np
from pathlib import Path
import logging
import face_recognition
import cv2
from typing import List, Dict, Optional
import json

# Try to import ollama, fallback if not available
try:
    import ollama
    OLLAMA_AVAILABLE = True
except ImportError:
    OLLAMA_AVAILABLE = False
    logging.warning("Ollama not available, will use BLIP only")

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="Avinash-EYE Enhanced AI Service")

# Global variables for models
blip_processor = None
blip_model = None
clip_processor = None
clip_model = None
device = None


class AnalyzeRequest(BaseModel):
    """Request model for image analysis."""
    image_path: str
    use_ollama: bool = True
    detect_faces: bool = True


class EmbedTextRequest(BaseModel):
    """Request model for text embedding."""
    query: str


class FaceSearchRequest(BaseModel):
    """Request model for face search."""
    image_path: str


class AnalyzeResponse(BaseModel):
    """Response model for image analysis."""
    description: str
    detailed_description: Optional[str] = None
    meta_tags: List[str] = []
    embedding: list[float]
    faces_detected: int = 0
    face_locations: List[List[int]] = []
    face_encodings: List[List[float]] = []


class EmbedTextResponse(BaseModel):
    """Response model for text embedding."""
    embedding: list[float]


class FaceSearchResponse(BaseModel):
    """Response model for face search."""
    face_encodings: List[List[float]]
    face_count: int


@app.on_event("startup")
async def load_models():
    """Load AI models on startup."""
    global blip_processor, blip_model, clip_processor, clip_model, device
    
    logger.info("Starting model loading process...")
    
    # Determine device
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    logger.info(f"Using device: {device}")
    
    try:
        # Load BLIP model
        logger.info("Loading BLIP model...")
        blip_processor = BlipProcessor.from_pretrained("Salesforce/blip-image-captioning-large")
        blip_model = BlipForConditionalGeneration.from_pretrained("Salesforce/blip-image-captioning-large")
        blip_model.to(device)
        blip_model.eval()
        logger.info("BLIP model loaded successfully!")
        
        # Load CLIP model
        logger.info("Loading CLIP model...")
        clip_processor = CLIPProcessor.from_pretrained("openai/clip-vit-base-patch32")
        clip_model = CLIPModel.from_pretrained("openai/clip-vit-base-patch32")
        clip_model.to(device)
        clip_model.eval()
        logger.info("CLIP model loaded successfully!")
        
        logger.info("All models loaded and ready!")
        
    except Exception as e:
        logger.error(f"Error loading models: {str(e)}")
        raise


def generate_detailed_caption_blip(image: Image.Image) -> str:
    """Generate detailed caption using BLIP."""
    inputs = blip_processor(image, return_tensors="pt").to(device)
    
    with torch.no_grad():
        out = blip_model.generate(
            **inputs,
            max_length=150,
            num_beams=5,
            temperature=1.0
        )
    
    return blip_processor.decode(out[0], skip_special_tokens=True)


def generate_ollama_description(image_path: str, blip_caption: str) -> Dict:
    """Generate very detailed description and meta tags using Ollama."""
    if not OLLAMA_AVAILABLE:
        return {
            "detailed_description": blip_caption,
            "meta_tags": extract_keywords(blip_caption)
        }
    
    try:
        prompt = f"""Based on this image caption: "{blip_caption}"

Please provide:
1. A VERY detailed description (3-4 sentences) including:
   - What is visible in the image
   - Colors, textures, and visual details
   - Setting/environment/background
   - Mood or atmosphere
   - Any notable objects, people, or elements

2. Meta tags (comma-separated keywords) for:
   - Main subjects
   - Colors
   - Style/mood
   - Objects
   - Setting

Format your response as JSON:
{{"detailed_description": "...", "meta_tags": ["tag1", "tag2", ...]}}"""

        response = ollama.generate(
            model='llama2',
            prompt=prompt,
            stream=False
        )
        
        result_text = response['response']
        
        # Try to parse JSON response
        try:
            result = json.loads(result_text)
            return result
        except json.JSONDecodeError:
            # Fallback if JSON parsing fails
            return {
                "detailed_description": result_text[:500],
                "meta_tags": extract_keywords(blip_caption)
            }
            
    except Exception as e:
        logger.error(f"Ollama generation failed: {str(e)}")
        return {
            "detailed_description": blip_caption,
            "meta_tags": extract_keywords(blip_caption)
        }


def extract_keywords(text: str) -> List[str]:
    """Extract keywords from text as fallback for meta tags."""
    # Simple keyword extraction
    words = text.lower().split()
    # Remove common words
    stop_words = {'a', 'an', 'the', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'that', 'this'}
    keywords = [w.strip('.,!?;:') for w in words if w not in stop_words and len(w) > 3]
    return list(set(keywords))[:10]  # Return top 10 unique keywords


def detect_faces(image: Image.Image) -> Dict:
    """Detect faces in image and return locations and encodings."""
    try:
        # Convert PIL to numpy array
        img_array = np.array(image)
        
        # Convert RGB to BGR for opencv
        img_bgr = cv2.cvtColor(img_array, cv2.COLOR_RGB2BGR)
        
        # Detect face locations
        face_locations = face_recognition.face_locations(img_array)
        
        # Get face encodings
        face_encodings = []
        if face_locations:
            face_encodings = face_recognition.face_encodings(img_array, face_locations)
        
        return {
            "count": len(face_locations),
            "locations": face_locations,
            "encodings": [encoding.tolist() for encoding in face_encodings]
        }
    except Exception as e:
        logger.error(f"Face detection failed: {str(e)}")
        return {"count": 0, "locations": [], "encodings": []}


def generate_image_embedding(image: Image.Image) -> np.ndarray:
    """Generate normalized embedding vector using CLIP."""
    inputs = clip_processor(images=image, return_tensors="pt").to(device)
    
    with torch.no_grad():
        image_features = clip_model.get_image_features(**inputs)
    
    embedding = image_features / image_features.norm(dim=-1, keepdim=True)
    return embedding.cpu().numpy().flatten()


def generate_text_embedding(text: str) -> np.ndarray:
    """Generate normalized embedding for text using CLIP."""
    inputs = clip_processor(text=[text], return_tensors="pt", padding=True).to(device)
    
    with torch.no_grad():
        text_features = clip_model.get_text_features(**inputs)
    
    embedding = text_features / text_features.norm(dim=-1, keepdim=True)
    return embedding.cpu().numpy().flatten()


@app.get("/health")
async def health_check():
    """Health check endpoint."""
    models_loaded = all([
        blip_processor is not None,
        blip_model is not None,
        clip_processor is not None,
        clip_model is not None
    ])
    
    return {
        "status": "healthy" if models_loaded else "initializing",
        "models_loaded": models_loaded,
        "device": str(device) if device else "unknown",
        "ollama_available": OLLAMA_AVAILABLE
    }


@app.post("/analyze", response_model=AnalyzeResponse)
async def analyze_image(request: AnalyzeRequest):
    """Analyze image with enhanced features."""
    try:
        if blip_model is None or clip_model is None:
            raise HTTPException(status_code=503, detail="Models not loaded yet")
        
        image_path = Path(request.image_path)
        if not image_path.exists():
            raise HTTPException(status_code=404, detail=f"Image not found: {request.image_path}")
        
        logger.info(f"Analyzing image: {request.image_path}")
        
        image = Image.open(image_path).convert("RGB")
        
        # Generate BLIP caption
        blip_caption = generate_detailed_caption_blip(image)
        logger.info(f"BLIP caption: {blip_caption[:100]}...")
        
        # Generate detailed description with Ollama
        detailed_info = {"detailed_description": blip_caption, "meta_tags": []}
        if request.use_ollama:
            detailed_info = generate_ollama_description(str(image_path), blip_caption)
        else:
            detailed_info["meta_tags"] = extract_keywords(blip_caption)
        
        # Generate embedding
        embedding = generate_image_embedding(image)
        
        # Detect faces
        face_info = {"count": 0, "locations": [], "encodings": []}
        if request.detect_faces:
            face_info = detect_faces(image)
            logger.info(f"Detected {face_info['count']} faces")
        
        return AnalyzeResponse(
            description=blip_caption,
            detailed_description=detailed_info.get("detailed_description", blip_caption),
            meta_tags=detailed_info.get("meta_tags", []),
            embedding=embedding.tolist(),
            faces_detected=face_info["count"],
            face_locations=face_info["locations"],
            face_encodings=face_info["encodings"]
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error analyzing image: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/embed-text", response_model=EmbedTextResponse)
async def embed_text(request: EmbedTextRequest):
    """Generate embedding for text query."""
    try:
        if clip_model is None:
            raise HTTPException(status_code=503, detail="Models not loaded yet")
        
        logger.info(f"Embedding text query: {request.query}")
        embedding = generate_text_embedding(request.query)
        
        return EmbedTextResponse(embedding=embedding.tolist())
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error embedding text: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/search-by-face", response_model=FaceSearchResponse)
async def search_by_face(request: FaceSearchRequest):
    """Extract face encodings from an image for face search."""
    try:
        image_path = Path(request.image_path)
        if not image_path.exists():
            raise HTTPException(status_code=404, detail=f"Image not found: {request.image_path}")
        
        logger.info(f"Extracting faces from: {request.image_path}")
        
        image = Image.open(image_path).convert("RGB")
        face_info = detect_faces(image)
        
        return FaceSearchResponse(
            face_encodings=face_info["encodings"],
            face_count=face_info["count"]
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error in face search: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/")
async def root():
    """Root endpoint."""
    return {
        "service": "Avinash-EYE Enhanced AI Service",
        "version": "2.0.0",
        "features": [
            "BLIP image captioning",
            "CLIP embeddings",
            "Ollama detailed descriptions" if OLLAMA_AVAILABLE else "BLIP descriptions only",
            "Face detection and recognition",
            "Meta tag generation"
        ],
        "endpoints": [
            "/health",
            "/analyze",
            "/embed-text",
            "/search-by-face"
        ]
    }

