/**
 * Tag Filter System
 * Handles multi-tag filtering with AND logic
 * 
 * Features:
 * - Click tags to toggle selection
 * - Visual feedback (green background when selected)
 * - URL parameter management
 * - Page reload with new filter
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
      let newTags = [...currentTags];
      
      // Toggle tag selection
      if (newTags.includes(tagId)) {
        // Remove tag from selection
        newTags = newTags.filter(id => id !== tagId);
      } else {
        // Add tag to selection
        newTags.push(tagId);
      }
      
      // Redirect with updated tag selection
      if (newTags.length > 0) {
        window.location.href = BASE_URL + 'tags/list.php?tags=' + newTags.join(',');
      } else {
        // No tags selected, show all
        window.location.href = BASE_URL + 'tags/list.php';
      }
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
