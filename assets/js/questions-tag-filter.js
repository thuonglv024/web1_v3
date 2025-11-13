/**
 * Tag Filter System for Questions List Page
 * Handles multi-tag filtering with module preservation
 * 
 * Features:
 * - Click tags to toggle selection
 * - Visual feedback (green background when selected)
 * - Preserves module filter when selecting tags
 * - URL parameter management
 */

document.addEventListener('DOMContentLoaded', function() {
  // Get all tag selector elements
  const tagSelectors = document.querySelectorAll('.tag-selector');
  
  // Parse current selected tags from URL
  const urlParams = new URLSearchParams(window.location.search);
  const currentTags = urlParams.get('tags') 
    ? urlParams.get('tags').split(',').map(Number) 
    : [];
  
  // Add click handler to each tag
  tagSelectors.forEach(tag => {
    tag.addEventListener('click', function() {
      const tagId = parseInt(this.getAttribute('data-tag-id'));
      const moduleId = this.getAttribute('data-module-id');
      let newTags = [...currentTags];
      
      // Toggle tag selection
      if (newTags.includes(tagId)) {
        // Remove tag from selection
        newTags = newTags.filter(id => id !== tagId);
      } else {
        // Add tag to selection
        newTags.push(tagId);
      }
      
      // Build URL with module and tags
      let url = BASE_URL + 'questions/list.php';
      const params = [];
      
      if (moduleId && moduleId !== 'all') {
        params.push('module=' + moduleId);
      }
      
      if (newTags.length > 0) {
        params.push('tags=' + newTags.join(','));
      }
      
      if (params.length > 0) {
        url += '?' + params.join('&');
      }
      
      window.location.href = url;
    });
    
    // Hover effect for non-selected tags
    tag.addEventListener('mouseover', function() {
      if (!this.classList.contains('selected')) {
        this.style.borderColor = '#22c55e';
      }
    });
    
    tag.addEventListener('mouseout', function() {
      if (!this.classList.contains('selected')) {
        this.style.borderColor = '#1f2937';
      }
    });
  });
});
