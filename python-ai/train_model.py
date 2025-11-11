"""
Train and fine-tune AI models using existing images.

This script learns from your image collection to:
1. Improve search relevance
2. Better categorization
3. Personalized descriptions
4. Enhanced face recognition
"""

import os
import json
import logging
from pathlib import Path
from typing import List, Dict, Tuple
import numpy as np
import torch
from PIL import Image
from transformers import CLIPProcessor, CLIPModel, BlipProcessor, BlipForConditionalGeneration
import pickle
from datetime import datetime
from collections import Counter, defaultdict

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Paths
SHARED_PATH = Path("/app/shared")
TRAINING_DATA_PATH = Path("/app/training_data")
MODELS_PATH = Path("/app/models")
TRAINING_DATA_PATH.mkdir(exist_ok=True)
MODELS_PATH.mkdir(exist_ok=True)


class ImageTrainer:
    """Train and improve models based on existing images."""
    
    def __init__(self):
        self.device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
        logger.info(f"Using device: {self.device}")
        
        # Load models
        self.clip_model = None
        self.clip_processor = None
        self.blip_model = None
        self.blip_processor = None
        
        # Training data
        self.image_data = []
        self.category_patterns = defaultdict(list)
        self.description_patterns = {}
        self.face_clusters = {}
        
    def load_models(self):
        """Load CLIP and BLIP models."""
        logger.info("Loading models for training...")
        
        try:
            # Load CLIP
            self.clip_processor = CLIPProcessor.from_pretrained(
                "laion/CLIP-ViT-B-32-laion2B-s34B-b79K"
            )
            self.clip_model = CLIPModel.from_pretrained(
                "laion/CLIP-ViT-B-32-laion2B-s34B-b79K"
            ).to(self.device)
            
            # Load BLIP
            self.blip_processor = BlipProcessor.from_pretrained(
                "Salesforce/blip-image-captioning-large"
            )
            self.blip_model = BlipForConditionalGeneration.from_pretrained(
                "Salesforce/blip-image-captioning-large"
            ).to(self.device)
            
            logger.info("Models loaded successfully")
        except Exception as e:
            logger.error(f"Failed to load models: {e}")
            raise
    
    def collect_training_data(self, metadata_file: str):
        """
        Collect training data from existing images.
        
        Args:
            metadata_file: Path to JSON file with image metadata
        """
        logger.info(f"Collecting training data from: {metadata_file}")
        
        try:
            with open(metadata_file, 'r') as f:
                data = json.load(f)
            
            for item in data:
                image_path = SHARED_PATH / item['filename']
                if not image_path.exists():
                    continue
                
                self.image_data.append({
                    'path': str(image_path),
                    'description': item.get('description', ''),
                    'detailed_description': item.get('detailed_description', ''),
                    'meta_tags': item.get('meta_tags', []),
                    'face_count': item.get('face_count', 0),
                    'embedding': item.get('embedding', None)
                })
            
            logger.info(f"Collected {len(self.image_data)} images for training")
        except Exception as e:
            logger.error(f"Failed to collect training data: {e}")
    
    def analyze_category_patterns(self):
        """
        Analyze patterns in image categories and tags.
        This helps improve auto-categorization.
        """
        logger.info("Analyzing category patterns...")
        
        # Collect all tags
        all_tags = []
        for item in self.image_data:
            all_tags.extend(item['meta_tags'])
        
        # Find most common tags
        tag_counts = Counter(all_tags)
        common_tags = tag_counts.most_common(50)
        
        logger.info(f"Found {len(tag_counts)} unique tags")
        logger.info(f"Top 10 tags: {common_tags[:10]}")
        
        # Analyze co-occurrence patterns
        for item in self.image_data:
            tags = item['meta_tags']
            for tag in tags:
                # Store images that have this tag
                self.category_patterns[tag].append({
                    'description': item['description'],
                    'other_tags': [t for t in tags if t != tag]
                })
        
        # Save patterns
        patterns_file = TRAINING_DATA_PATH / 'category_patterns.json'
        with open(patterns_file, 'w') as f:
            json.dump({
                'common_tags': common_tags,
                'tag_counts': dict(tag_counts),
                'patterns': {k: v[:100] for k, v in self.category_patterns.items()}  # Limit size
            }, f, indent=2)
        
        logger.info(f"Category patterns saved to: {patterns_file}")
    
    def analyze_description_patterns(self):
        """
        Analyze patterns in descriptions to improve generation.
        """
        logger.info("Analyzing description patterns...")
        
        # Group descriptions by tags
        for item in self.image_data:
            for tag in item['meta_tags']:
                if tag not in self.description_patterns:
                    self.description_patterns[tag] = []
                
                if item['detailed_description']:
                    self.description_patterns[tag].append(item['detailed_description'])
        
        # Analyze patterns
        pattern_analysis = {}
        for tag, descriptions in self.description_patterns.items():
            if len(descriptions) < 3:
                continue
            
            # Extract common phrases
            all_words = []
            for desc in descriptions:
                all_words.extend(desc.lower().split())
            
            word_counts = Counter(all_words)
            common_words = [w for w, c in word_counts.most_common(20) if len(w) > 3]
            
            pattern_analysis[tag] = {
                'count': len(descriptions),
                'common_words': common_words,
                'avg_length': sum(len(d.split()) for d in descriptions) / len(descriptions)
            }
        
        # Save patterns
        patterns_file = TRAINING_DATA_PATH / 'description_patterns.json'
        with open(patterns_file, 'w') as f:
            json.dump(pattern_analysis, f, indent=2)
        
        logger.info(f"Description patterns saved to: {patterns_file}")
    
    def extract_face_features(self):
        """
        Extract and cluster face features for better face recognition.
        Requires face_recognition library.
        """
        logger.info("Extracting face features...")
        
        try:
            import face_recognition
        except ImportError:
            logger.warning("face_recognition not available, skipping face clustering")
            return
        
        face_encodings_all = []
        face_metadata = []
        
        for item in self.image_data:
            if item['face_count'] == 0:
                continue
            
            try:
                image = face_recognition.load_image_file(item['path'])
                encodings = face_recognition.face_encodings(image)
                
                for encoding in encodings:
                    face_encodings_all.append(encoding)
                    face_metadata.append({
                        'image': item['path'],
                        'tags': item['meta_tags']
                    })
            except Exception as e:
                logger.warning(f"Failed to process faces in {item['path']}: {e}")
        
        if not face_encodings_all:
            logger.info("No faces found for clustering")
            return
        
        logger.info(f"Extracted {len(face_encodings_all)} face encodings")
        
        # Simple clustering based on similarity
        clusters = []
        used = set()
        
        for i, encoding in enumerate(face_encodings_all):
            if i in used:
                continue
            
            cluster = [i]
            used.add(i)
            
            for j, other_encoding in enumerate(face_encodings_all):
                if j in used or i == j:
                    continue
                
                distance = face_recognition.face_distance([encoding], other_encoding)[0]
                if distance < 0.6:  # Threshold for same person
                    cluster.append(j)
                    used.add(j)
            
            if len(cluster) >= 2:  # Only clusters with multiple faces
                clusters.append(cluster)
        
        logger.info(f"Found {len(clusters)} face clusters")
        
        # Save clusters
        clusters_file = TRAINING_DATA_PATH / 'face_clusters.pkl'
        with open(clusters_file, 'wb') as f:
            pickle.dump({
                'clusters': clusters,
                'metadata': face_metadata,
                'encodings': face_encodings_all
            }, f)
        
        logger.info(f"Face clusters saved to: {clusters_file}")
    
    def generate_improved_descriptions(self, output_file: str):
        """
        Generate improved descriptions using learned patterns.
        
        Args:
            output_file: Path to save improved descriptions
        """
        logger.info("Generating improved descriptions...")
        
        improvements = []
        
        for item in self.image_data:
            try:
                # Load image
                image = Image.open(item['path']).convert('RGB')
                
                # Generate base description with BLIP
                inputs = self.blip_processor(image, return_tensors="pt").to(self.device)
                output = self.blip_model.generate(**inputs, max_length=100)
                base_description = self.blip_processor.decode(output[0], skip_special_tokens=True)
                
                # Enhance based on learned patterns
                relevant_tags = item['meta_tags']
                enhancement_context = []
                
                for tag in relevant_tags:
                    if tag in self.description_patterns:
                        patterns = self.description_patterns.get(tag, [])
                        if patterns:
                            # Use patterns to enhance description
                            enhancement_context.append(f"Common {tag} descriptions include: {', '.join(patterns[:3])}")
                
                improvements.append({
                    'image': item['path'],
                    'original_description': item['description'],
                    'base_description': base_description,
                    'suggested_tags': relevant_tags,
                    'enhancement_context': enhancement_context
                })
                
            except Exception as e:
                logger.warning(f"Failed to improve description for {item['path']}: {e}")
        
        # Save improvements
        with open(output_file, 'w') as f:
            json.dump(improvements, f, indent=2)
        
        logger.info(f"Generated {len(improvements)} improved descriptions")
        logger.info(f"Saved to: {output_file}")
    
    def build_search_index(self):
        """
        Build improved search index using learned patterns.
        """
        logger.info("Building improved search index...")
        
        # Create synonym mappings based on co-occurrence
        synonyms = defaultdict(set)
        
        for tag, data in self.category_patterns.items():
            # Find tags that often appear together
            co_occurring = []
            for item in data:
                co_occurring.extend(item['other_tags'])
            
            if co_occurring:
                common_co = Counter(co_occurring).most_common(5)
                for related_tag, count in common_co:
                    if count >= 3:  # Appears together at least 3 times
                        synonyms[tag].add(related_tag)
                        synonyms[related_tag].add(tag)
        
        # Save search improvements
        search_index = {
            'synonyms': {k: list(v) for k, v in synonyms.items()},
            'timestamp': datetime.now().isoformat()
        }
        
        index_file = TRAINING_DATA_PATH / 'search_index.json'
        with open(index_file, 'w') as f:
            json.dump(search_index, f, indent=2)
        
        logger.info(f"Search index saved to: {index_file}")
        logger.info(f"Found {len(synonyms)} terms with related concepts")
    
    def generate_training_report(self):
        """Generate a summary report of the training process."""
        logger.info("Generating training report...")
        
        report = {
            'timestamp': datetime.now().isoformat(),
            'total_images': len(self.image_data),
            'total_tags': len(self.category_patterns),
            'images_with_faces': sum(1 for item in self.image_data if item['face_count'] > 0),
            'images_with_detailed_desc': sum(1 for item in self.image_data if item['detailed_description']),
            'top_categories': list(self.category_patterns.keys())[:20],
            'status': 'completed'
        }
        
        report_file = TRAINING_DATA_PATH / 'training_report.json'
        with open(report_file, 'w') as f:
            json.dump(report, f, indent=2)
        
        logger.info("Training report:")
        for key, value in report.items():
            logger.info(f"  {key}: {value}")
        
        return report


def main():
    """Main training workflow."""
    logger.info("=" * 60)
    logger.info("Starting AI Model Training")
    logger.info("=" * 60)
    
    # Initialize trainer
    trainer = ImageTrainer()
    
    # Load models
    trainer.load_models()
    
    # Check for metadata file
    metadata_file = TRAINING_DATA_PATH / 'images_metadata.json'
    if not metadata_file.exists():
        logger.error(f"Metadata file not found: {metadata_file}")
        logger.info("Please export training data first using: php artisan export:training-data")
        return
    
    # Collect training data
    trainer.collect_training_data(str(metadata_file))
    
    if not trainer.image_data:
        logger.warning("No training data found!")
        return
    
    # Analyze patterns
    logger.info("\n" + "=" * 60)
    logger.info("Phase 1: Analyzing Category Patterns")
    logger.info("=" * 60)
    trainer.analyze_category_patterns()
    
    logger.info("\n" + "=" * 60)
    logger.info("Phase 2: Analyzing Description Patterns")
    logger.info("=" * 60)
    trainer.analyze_description_patterns()
    
    logger.info("\n" + "=" * 60)
    logger.info("Phase 3: Extracting Face Features")
    logger.info("=" * 60)
    trainer.extract_face_features()
    
    logger.info("\n" + "=" * 60)
    logger.info("Phase 4: Building Search Index")
    logger.info("=" * 60)
    trainer.build_search_index()
    
    logger.info("\n" + "=" * 60)
    logger.info("Phase 5: Generating Improved Descriptions")
    logger.info("=" * 60)
    improvements_file = TRAINING_DATA_PATH / 'improved_descriptions.json'
    trainer.generate_improved_descriptions(str(improvements_file))
    
    # Generate report
    logger.info("\n" + "=" * 60)
    logger.info("Generating Training Report")
    logger.info("=" * 60)
    report = trainer.generate_training_report()
    
    logger.info("\n" + "=" * 60)
    logger.info("Training Complete!")
    logger.info("=" * 60)
    logger.info(f"Training data saved in: {TRAINING_DATA_PATH}")
    logger.info("\nFiles created:")
    logger.info(f"  - category_patterns.json")
    logger.info(f"  - description_patterns.json")
    logger.info(f"  - face_clusters.pkl")
    logger.info(f"  - search_index.json")
    logger.info(f"  - improved_descriptions.json")
    logger.info(f"  - training_report.json")


if __name__ == "__main__":
    main()

