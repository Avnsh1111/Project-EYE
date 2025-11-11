"""
FastAPI service for image analysis and semantic search using local AI models.
Uses BLIP for image captioning and CLIP for embeddings.
Enhanced with learned patterns from your image collection.
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
from typing import Optional, List
import json
import base64
import io
import os

# Import enhanced analysis
try:
    from enhanced_analysis import enhance_image_analysis
    ENHANCED_ANALYSIS_AVAILABLE = True
    logger = logging.getLogger(__name__)
    logger.info("Enhanced analysis module loaded - using learned patterns")
except ImportError:
    ENHANCED_ANALYSIS_AVAILABLE = False
    logger = logging.getLogger(__name__)
    logger.warning("Enhanced analysis not available - using base models only")

# Try to import ollama
try:
    import ollama
    OLLAMA_AVAILABLE = True
    logger = logging.getLogger(__name__)
    
    # Configure Ollama client to use Docker service
    OLLAMA_HOST = os.getenv('OLLAMA_HOST', 'http://localhost:11434')
    ollama_client = ollama.Client(host=OLLAMA_HOST)
    
    logger.info(f"Ollama is available at {OLLAMA_HOST}")
except ImportError:
    OLLAMA_AVAILABLE = False
    ollama_client = None
    logger = logging.getLogger(__name__)
    logger.warning("Ollama not installed, will use BLIP only")

# Try to import face_recognition
try:
    import face_recognition
    import cv2
    FACE_RECOGNITION_AVAILABLE = True
    logger.info("Face recognition is available")
except ImportError:
    FACE_RECOGNITION_AVAILABLE = False
    logger.warning("Face recognition not installed")

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="Avinash-EYE AI Service")

# Global variables for models
blip_processor = None
blip_model = None
clip_processor = None
clip_model = None
device = None


class AnalyzeRequest(BaseModel):
    """Request model for image analysis."""
    image_path: str
    captioning_model: Optional[str] = "Salesforce/blip-image-captioning-large"
    embedding_model: Optional[str] = "openai/clip-vit-base-patch32"
    face_detection_enabled: Optional[bool] = True
    ollama_enabled: Optional[bool] = False
    ollama_model: Optional[str] = "llava"


class EmbedTextRequest(BaseModel):
    """Request model for text embedding."""
    query: str


class AnalyzeResponse(BaseModel):
    """Response model for image analysis."""
    description: str
    detailed_description: Optional[str] = None
    meta_tags: List[str] = []
    embedding: list[float]
    face_count: int = 0
    face_encodings: List[List[float]] = []  # Legacy support
    faces: List[dict] = []  # New: detailed face data with locations


class EmbedTextResponse(BaseModel):
    """Response model for text embedding."""
    embedding: list[float]


@app.on_event("startup")
async def load_models():
    """Load AI models on startup."""
    global blip_processor, blip_model, clip_processor, clip_model, device
    
    logger.info("Starting model loading process...")
    
    # Determine device (CPU or CUDA)
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    logger.info(f"Using device: {device}")
    
    try:
        # Load BLIP model for image captioning
        logger.info("Loading BLIP model for image captioning...")
        blip_processor = BlipProcessor.from_pretrained("Salesforce/blip-image-captioning-large")
        blip_model = BlipForConditionalGeneration.from_pretrained("Salesforce/blip-image-captioning-large")
        blip_model.to(device)
        blip_model.eval()
        logger.info("BLIP model loaded successfully!")
        
        # Load CLIP model for embeddings
        logger.info("Loading CLIP model for embeddings...")
        clip_processor = CLIPProcessor.from_pretrained("openai/clip-vit-base-patch32")
        clip_model = CLIPModel.from_pretrained("openai/clip-vit-base-patch32")
        clip_model.to(device)
        clip_model.eval()
        logger.info("CLIP model loaded successfully!")
        
        logger.info("All models loaded and ready!")
        
    except Exception as e:
        logger.error(f"Error loading models: {str(e)}")
        raise


def generate_detailed_caption(image: Image.Image) -> str:
    """
    Generate a detailed caption for an image using BLIP.
    
    Args:
        image: PIL Image object
        
    Returns:
        Detailed caption string
    """
    # Generate unconditional caption
    inputs = blip_processor(image, return_tensors="pt").to(device)
    
    with torch.no_grad():
        out = blip_model.generate(
            **inputs,
            max_length=150,
            num_beams=5,
            temperature=1.0,
            do_sample=False
        )
    
    caption = blip_processor.decode(out[0], skip_special_tokens=True)
    
    # Generate additional context with prompts
    prompts = [
        "a detailed description of",
        "this image shows",
    ]
    
    additional_details = []
    for prompt in prompts:
        inputs = blip_processor(image, text=prompt, return_tensors="pt").to(device)
        with torch.no_grad():
            out = blip_model.generate(
                **inputs,
                max_length=100,
                num_beams=3,
                temperature=1.0
            )
        detail = blip_processor.decode(out[0], skip_special_tokens=True)
        if detail and detail not in additional_details:
            additional_details.append(detail)
    
    # Combine all descriptions
    full_description = caption
    if additional_details:
        full_description += ". " + ". ".join(additional_details)
    
    return full_description


def generate_image_embedding(image: Image.Image) -> np.ndarray:
    """
    Generate normalized embedding vector for an image using CLIP.
    
    Args:
        image: PIL Image object
        
    Returns:
        Normalized embedding vector as numpy array
    """
    inputs = clip_processor(images=image, return_tensors="pt").to(device)
    
    with torch.no_grad():
        image_features = clip_model.get_image_features(**inputs)
    
    # Normalize the embedding
    embedding = image_features / image_features.norm(dim=-1, keepdim=True)
    
    return embedding.cpu().numpy().flatten()


def generate_text_embedding(text: str) -> np.ndarray:
    """
    Generate normalized embedding vector for text using CLIP.
    
    Args:
        text: Input text query
        
    Returns:
        Normalized embedding vector as numpy array
    """
    inputs = clip_processor(text=[text], return_tensors="pt", padding=True).to(device)
    
    with torch.no_grad():
        text_features = clip_model.get_text_features(**inputs)
    
    # Normalize the embedding
    embedding = text_features / text_features.norm(dim=-1, keepdim=True)
    
    return embedding.cpu().numpy().flatten()


def generate_ollama_description(image_path: str, blip_caption: str, ollama_model: str = "llava") -> dict:
    """
    Generate detailed description using Ollama vision model.
    
    Args:
        image_path: Path to the image file
        blip_caption: Initial caption from BLIP
        ollama_model: Ollama model to use (llava, etc.)
        
    Returns:
        Dict with detailed_description and meta_tags
    """
    if not OLLAMA_AVAILABLE:
        logger.warning("Ollama not available, using BLIP caption only")
        return {
            "detailed_description": blip_caption,
            "meta_tags": extract_keywords(blip_caption)
        }
    
    try:
        logger.info(f"Generating Ollama description with model: {ollama_model}")
        
        # Convert image to base64 for Ollama
        with open(image_path, "rb") as img_file:
            img_data = base64.b64encode(img_file.read()).decode('utf-8')
        
        prompt = f"""Analyze this image in detail. The basic caption is: "{blip_caption}"

Please provide:
1. A very detailed description (3-4 sentences) including colors, objects, people, setting, mood, and any notable details.
2. A list of meta tags/keywords (comma-separated) for searching, including: main subjects, colors, objects, setting, style.

Format as JSON: {{"detailed_description": "...", "meta_tags": ["tag1", "tag2", ...]}}"""

        # Call Ollama with image using the configured client
        response = ollama_client.generate(
            model=ollama_model,
            prompt=prompt,
            images=[img_data],
            stream=False
        )
        
        result_text = response.get('response', '')
        logger.info(f"Ollama response: {result_text[:200]}...")
        
        # Try to parse JSON
        try:
            # Find JSON in response
            start_idx = result_text.find('{')
            end_idx = result_text.rfind('}') + 1
            if start_idx != -1 and end_idx > start_idx:
                json_str = result_text[start_idx:end_idx]
                result = json.loads(json_str)
                return {
                    "detailed_description": result.get("detailed_description", result_text),
                    "meta_tags": result.get("meta_tags", extract_keywords(blip_caption))
                }
        except (json.JSONDecodeError, ValueError) as e:
            logger.warning(f"JSON parse error: {e}, using raw response")
        
        # Fallback
        return {
            "detailed_description": result_text if result_text else blip_caption,
            "meta_tags": extract_keywords(blip_caption)
        }
        
    except Exception as e:
        logger.error(f"Ollama generation failed: {str(e)}")
        return {
            "detailed_description": blip_caption,
            "meta_tags": extract_keywords(blip_caption)
        }


def extract_keywords(text: str) -> List[str]:
    """Extract keywords from text for meta tags."""
    words = text.lower().split()
    stop_words = {'a', 'an', 'the', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'that', 'this', 'and', 'or'}
    keywords = [w.strip('.,!?;:()[]') for w in words if w not in stop_words and len(w) > 3]
    return list(set(keywords))[:15]


def detect_faces(image: Image.Image) -> dict:
    """
    Detect faces in image.
    
    Args:
        image: PIL Image
        
    Returns:
        Dict with face count and detailed face data
    """
    if not FACE_RECOGNITION_AVAILABLE:
        return {"count": 0, "faces": []}
    
    try:
        img_array = np.array(image)
        face_locations = face_recognition.face_locations(img_array)
        
        if not face_locations:
            return {"count": 0, "faces": []}
        
        face_encodings = face_recognition.face_encodings(img_array, face_locations)
        
        # Build detailed face data
        faces = []
        for i, (location, encoding) in enumerate(zip(face_locations, face_encodings)):
            top, right, bottom, left = location
            faces.append({
                "encoding": encoding.tolist(),
                "location": {
                    "top": int(top),
                    "right": int(right),
                    "bottom": int(bottom),
                    "left": int(left)
                },
                "confidence": 1.0  # face_recognition doesn't provide confidence, use 1.0
            })
        
        return {
            "count": len(faces),
            "faces": faces
        }
    except Exception as e:
        logger.error(f"Face detection error: {str(e)}")
        return {"count": 0, "faces": []}


@app.get("/health")
async def health_check():
    """Health check endpoint."""
    models_loaded = all([
        blip_processor is not None,
        blip_model is not None,
        clip_processor is not None,
        clip_model is not None
    ])
    
    # Check if Ollama service is actually running and responsive
    ollama_running = False
    if OLLAMA_AVAILABLE and ollama_client:
        try:
            # Try to list models to check if Ollama is responsive
            ollama_client.list()
            ollama_running = True
            logger.info("Ollama service is running and responsive")
        except Exception as e:
            logger.warning(f"Ollama available but service not responding: {e}")
    
    return {
        "status": "healthy" if models_loaded else "initializing",
        "models_loaded": models_loaded,
        "device": str(device) if device else "unknown",
        "ollama_available": ollama_running,
        "face_recognition_available": FACE_RECOGNITION_AVAILABLE
    }


@app.post("/analyze", response_model=AnalyzeResponse)
async def analyze_image(request: AnalyzeRequest):
    """
    Analyze an image and return detailed description and embedding.
    
    Args:
        request: AnalyzeRequest with image_path and options
        
    Returns:
        AnalyzeResponse with description, detailed_description, meta_tags, embedding, and face info
    """
    try:
        # Check if models are loaded
        if blip_model is None or clip_model is None:
            raise HTTPException(status_code=503, detail="Models not loaded yet")
        
        # Load image
        image_path = Path(request.image_path)
        if not image_path.exists():
            raise HTTPException(status_code=404, detail=f"Image not found: {request.image_path}")
        
        logger.info(f"Analyzing image: {request.image_path}")
        logger.info(f"Settings: ollama_enabled={request.ollama_enabled}, ollama_model={request.ollama_model}, face_detection={request.face_detection_enabled}")
        
        # Open and convert image
        image = Image.open(image_path).convert("RGB")
        
        # Generate BLIP caption
        description = generate_detailed_caption(image)
        logger.info(f"BLIP caption: {description[:100]}...")
        
        # Generate detailed description and meta tags
        detailed_description = description
        meta_tags = extract_keywords(description)
        
        if request.ollama_enabled and OLLAMA_AVAILABLE:
            logger.info(f"Using Ollama model: {request.ollama_model}")
            ollama_result = generate_ollama_description(str(image_path), description, request.ollama_model)
            detailed_description = ollama_result.get("detailed_description", description)
            meta_tags = ollama_result.get("meta_tags", meta_tags)
            logger.info(f"Ollama detailed description: {detailed_description[:100]}...")
        
        # Generate embedding
        embedding = generate_image_embedding(image)
        logger.info(f"Generated embedding with shape: {embedding.shape}")
        
        # Detect faces
        face_info = {"count": 0, "encodings": []}
        if request.face_detection_enabled:
            face_info = detect_faces(image)
            logger.info(f"Detected {face_info['count']} faces")
        
        # Prepare base analysis result
        analysis_result = {
            'description': description,
            'detailed_description': detailed_description,
            'meta_tags': meta_tags,
            'face_count': face_info["count"]
        }
        
        # Apply enhanced analysis if available
        if ENHANCED_ANALYSIS_AVAILABLE:
            try:
                analysis_result = enhance_image_analysis(analysis_result)
                logger.info("Applied learned patterns to improve analysis")
            except Exception as e:
                logger.warning(f"Enhanced analysis failed, using base result: {e}")
        
        return AnalyzeResponse(
            description=analysis_result['description'],
            detailed_description=analysis_result.get('detailed_description', detailed_description),
            meta_tags=analysis_result.get('meta_tags', meta_tags),
            embedding=embedding.tolist(),
            face_count=face_info["count"],
            face_encodings=face_info.get("encodings", []),  # Legacy support
            faces=face_info.get("faces", [])  # New: detailed face data
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error analyzing image: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/embed-text", response_model=EmbedTextResponse)
async def embed_text(request: EmbedTextRequest):
    """
    Generate embedding for text query.
    
    Args:
        request: EmbedTextRequest with query text
        
    Returns:
        EmbedTextResponse with embedding
    """
    try:
        # Check if models are loaded
        if clip_model is None:
            raise HTTPException(status_code=503, detail="Models not loaded yet")
        
        logger.info(f"Embedding text query: {request.query}")
        
        # Generate text embedding
        embedding = generate_text_embedding(request.query)
        logger.info(f"Generated text embedding with shape: {embedding.shape}")
        
        return EmbedTextResponse(
            embedding=embedding.tolist()
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error embedding text: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/api/model-status")
async def get_model_status():
    """
    Get the status of loaded models.
    
    Returns:
        Dictionary with model status information
    """
    loaded_models = []
    
    if blip_model is not None:
        loaded_models.append("Salesforce/blip-image-captioning-large")
    if clip_model is not None:
        loaded_models.append("openai/clip-vit-base-patch32")
    
    # Check Ollama availability
    ollama_running = False
    if OLLAMA_AVAILABLE:
        try:
            ollama_client.list()
            ollama_running = True
            logger.info("Ollama service is running and responsive")
        except Exception as e:
            logger.warning(f"Ollama not responsive: {e}")
            ollama_running = False
    
    return {
        "status": "online" if loaded_models else "offline",
        "models": loaded_models,
        "loaded_models": loaded_models,  # For compatibility
        "downloading": [],  # Can be extended to track downloads
        "device": str(device) if device else "unknown",
        "ollama_available": ollama_running,
        "face_recognition_available": FACE_RECOGNITION_AVAILABLE
    }


@app.post("/api/preload-models")
async def preload_models(request: dict):
    """
    Preload models based on configuration.
    This ensures models are downloaded and loaded before actual use.
    
    Args:
        request: Dict with captioning_model and embedding_model
        
    Returns:
        Success status
    """
    try:
        captioning_model = request.get('captioning_model', 'Salesforce/blip-image-captioning-large')
        embedding_model = request.get('embedding_model', 'openai/clip-vit-base-patch32')
        
        logger.info(f"Preloading models: {captioning_model}, {embedding_model}")
        
        # Models are already loaded on startup for now
        # In future, this can dynamically load different models
        
        return {
            "success": True,
            "message": "Models already loaded",
            "captioning_model": captioning_model,
            "embedding_model": embedding_model
        }
    except Exception as e:
        logger.error(f"Error preloading models: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/")
async def root():
    """Root endpoint."""
    return {
        "service": "Avinash-EYE AI Service",
        "version": "1.0.0",
        "endpoints": [
            "/health",
            "/analyze",
            "/embed-text",
            "/api/model-status",
            "/api/preload-models"
        ]
    }

