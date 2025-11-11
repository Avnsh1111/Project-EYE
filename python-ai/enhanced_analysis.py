"""
Enhanced image analysis using trained patterns.

This script uses learned patterns from your image collection to:
1. Generate better descriptions
2. Improve categorization
3. Enhanced face detection with clustering
"""

import json
import logging
from pathlib import Path
from typing import Dict, List, Optional
import numpy as np
from PIL import Image
import pickle

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

TRAINING_DATA_PATH = Path("/app/training_data")


class EnhancedAnalyzer:
    """Enhanced image analysis using learned patterns."""
    
    def __init__(self):
        self.category_patterns = {}
        self.description_patterns = {}
        self.search_index = {}
        self.face_clusters = None
        self.load_trained_data()
    
    def load_trained_data(self):
        """Load previously trained patterns."""
        try:
            # Load category patterns
            patterns_file = TRAINING_DATA_PATH / 'category_patterns.json'
            if patterns_file.exists():
                with open(patterns_file, 'r') as f:
                    data = json.load(f)
                    self.category_patterns = data.get('patterns', {})
                logger.info(f"Loaded {len(self.category_patterns)} category patterns")
            
            # Load description patterns
            desc_file = TRAINING_DATA_PATH / 'description_patterns.json'
            if desc_file.exists():
                with open(desc_file, 'r') as f:
                    self.description_patterns = json.load(f)
                logger.info(f"Loaded description patterns for {len(self.description_patterns)} tags")
            
            # Load search index
            search_file = TRAINING_DATA_PATH / 'search_index.json'
            if search_file.exists():
                with open(search_file, 'r') as f:
                    self.search_index = json.load(f)
                logger.info("Loaded search index")
            
            # Load face clusters
            clusters_file = TRAINING_DATA_PATH / 'face_clusters.pkl'
            if clusters_file.exists():
                with open(clusters_file, 'rb') as f:
                    self.face_clusters = pickle.load(f)
                logger.info(f"Loaded {len(self.face_clusters.get('clusters', []))} face clusters")
                
        except Exception as e:
            logger.warning(f"Some trained data could not be loaded: {e}")
            logger.info("Will use base models only")
    
    def enhance_description(self, base_description: str, tags: List[str]) -> str:
        """
        Enhance a base description using learned patterns.
        
        Args:
            base_description: Base description from BLIP
            tags: Detected meta tags
            
        Returns:
            Enhanced description
        """
        if not self.description_patterns or not tags:
            return base_description
        
        # Find relevant patterns
        enhancements = []
        for tag in tags:
            pattern = self.description_patterns.get(tag, {})
            common_words = pattern.get('common_words', [])
            
            # Add context from learned patterns
            if common_words:
                # Check if any common words are missing from base description
                missing_context = [w for w in common_words[:5] 
                                 if w not in base_description.lower()]
                if missing_context:
                    enhancements.append(f"commonly associated with {', '.join(missing_context[:3])}")
        
        if enhancements:
            enhanced = f"{base_description}. This image is {enhancements[0]}"
            return enhanced
        
        return base_description
    
    def improve_tags(self, initial_tags: List[str]) -> List[str]:
        """
        Improve tags using learned co-occurrence patterns.
        
        Args:
            initial_tags: Initial tags from base analysis
            
        Returns:
            Improved list of tags
        """
        if not self.search_index or 'synonyms' not in self.search_index:
            return initial_tags
        
        improved_tags = set(initial_tags)
        synonyms = self.search_index['synonyms']
        
        # Add related tags based on learned patterns
        for tag in initial_tags:
            if tag in synonyms:
                # Add highly related tags (limit to avoid tag explosion)
                related = synonyms[tag][:2]  # Top 2 related tags
                improved_tags.update(related)
        
        return list(improved_tags)
    
    def identify_face_cluster(self, face_encoding: np.ndarray) -> Optional[int]:
        """
        Identify which cluster a face belongs to.
        
        Args:
            face_encoding: Face encoding to match
            
        Returns:
            Cluster ID or None
        """
        if not self.face_clusters:
            return None
        
        try:
            import face_recognition
            
            clusters = self.face_clusters['clusters']
            encodings = self.face_clusters['encodings']
            
            for cluster_id, cluster_indices in enumerate(clusters):
                # Check against cluster members
                cluster_encodings = [encodings[i] for i in cluster_indices[:5]]  # Sample 5
                distances = face_recognition.face_distance(cluster_encodings, face_encoding)
                
                if np.min(distances) < 0.6:  # Match threshold
                    return cluster_id
            
        except Exception as e:
            logger.warning(f"Face clustering failed: {e}")
        
        return None
    
    def get_category_suggestions(self, description: str, tags: List[str]) -> List[str]:
        """
        Suggest additional categories based on patterns.
        
        Args:
            description: Image description
            tags: Current tags
            
        Returns:
            List of suggested categories
        """
        if not self.category_patterns:
            return []
        
        suggestions = set()
        description_lower = description.lower()
        
        # Find patterns that match description
        for category, patterns in self.category_patterns.items():
            if category in tags:
                continue
            
            # Check if category keywords appear in description
            for pattern in patterns[:10]:  # Sample patterns
                pattern_desc = pattern.get('description', '').lower()
                if pattern_desc and pattern_desc in description_lower:
                    suggestions.add(category)
                    break
        
        return list(suggestions)[:5]  # Top 5 suggestions
    
    def analyze_image_context(self, tags: List[str], face_count: int) -> Dict:
        """
        Analyze image context using learned patterns.
        
        Args:
            tags: Image tags
            face_count: Number of faces detected
            
        Returns:
            Context analysis
        """
        context = {
            'primary_category': None,
            'secondary_categories': [],
            'scene_type': None,
            'confidence': 0.0
        }
        
        if not tags:
            return context
        
        # Determine primary category based on learned patterns
        tag_importance = {}
        for tag in tags:
            # Use category pattern frequency as importance score
            if tag in self.category_patterns:
                tag_importance[tag] = len(self.category_patterns[tag])
        
        if tag_importance:
            sorted_tags = sorted(tag_importance.items(), key=lambda x: x[1], reverse=True)
            context['primary_category'] = sorted_tags[0][0]
            context['secondary_categories'] = [t[0] for t in sorted_tags[1:4]]
            context['confidence'] = min(sorted_tags[0][1] / 100, 1.0)
        
        # Determine scene type
        if face_count > 0:
            if face_count == 1:
                context['scene_type'] = 'portrait'
            elif face_count >= 2:
                context['scene_type'] = 'group'
        elif any(tag in ['landscape', 'nature', 'outdoor'] for tag in tags):
            context['scene_type'] = 'landscape'
        elif any(tag in ['food', 'meal', 'dish'] for tag in tags):
            context['scene_type'] = 'food'
        else:
            context['scene_type'] = 'general'
        
        return context


# Global analyzer instance
_analyzer = None

def get_analyzer() -> EnhancedAnalyzer:
    """Get or create analyzer instance."""
    global _analyzer
    if _analyzer is None:
        _analyzer = EnhancedAnalyzer()
    return _analyzer


def enhance_image_analysis(analysis_result: Dict) -> Dict:
    """
    Enhance analysis results using trained patterns.
    
    Args:
        analysis_result: Base analysis from BLIP/CLIP
        
    Returns:
        Enhanced analysis result
    """
    analyzer = get_analyzer()
    
    # Extract base data
    base_description = analysis_result.get('description', '')
    base_tags = analysis_result.get('meta_tags', [])
    face_count = analysis_result.get('face_count', 0)
    
    # Enhance description
    enhanced_description = analyzer.enhance_description(base_description, base_tags)
    
    # Improve tags
    improved_tags = analyzer.improve_tags(base_tags)
    
    # Get category suggestions
    suggested_categories = analyzer.get_category_suggestions(base_description, improved_tags)
    
    # Analyze context
    context = analyzer.analyze_image_context(improved_tags, face_count)
    
    # Add enhancements to result
    analysis_result['enhanced_description'] = enhanced_description
    analysis_result['meta_tags'] = improved_tags
    analysis_result['suggested_categories'] = suggested_categories
    analysis_result['context'] = context
    
    # If detailed description is missing but we have enhancement, use it
    if not analysis_result.get('detailed_description'):
        analysis_result['detailed_description'] = enhanced_description
    
    return analysis_result


if __name__ == "__main__":
    # Test the analyzer
    logger.info("Testing Enhanced Analyzer...")
    
    analyzer = get_analyzer()
    
    # Test with sample data
    test_result = {
        'description': 'a woman wearing a red dress',
        'meta_tags': ['woman', 'dress', 'fashion'],
        'face_count': 1
    }
    
    enhanced = enhance_image_analysis(test_result)
    
    logger.info("Enhanced result:")
    logger.info(json.dumps(enhanced, indent=2))

