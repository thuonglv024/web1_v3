document.addEventListener('DOMContentLoaded',()=>{
  const filterScopes = document.querySelectorAll('[data-search-scope]');
  const searchInputs = [
    document.getElementById('global-search'),
    document.getElementById('home-search') // fallback if any legacy pages still include it
  ].filter(Boolean);

  if (searchInputs.length && filterScopes.length) {
    const handleFilter = (query)=>{
      const term = (query || '').trim().toLowerCase();
      filterScopes.forEach(scope => {
        const items = scope.querySelectorAll('[data-search-item]');
        items.forEach(item => {
          const text = item.textContent.toLowerCase();
          item.style.display = (!term || text.includes(term)) ? '' : 'none';
        });
      });
    };

    searchInputs.forEach(input => {
      input.addEventListener('input', ()=> handleFilter(input.value));
    });
  }

  // Đã xóa hiệu ứng đổi màu khi cuộn thanh điều hướng
});
