function search(searchInputId, searchResultsId, apiUrl, resultTemplate) {
    const searchInput = document.querySelector(`#${searchInputId}`);
    const searchResults = document.querySelector(`#${searchResultsId}`);
    const resultsList = searchResults.querySelector('ul');
  
    searchInput.addEventListener('input', function () {
      const query = this.value.trim();
  
      if (query.length > 1) {
        // json request to display results
        fetch(`${apiUrl}?query=${encodeURIComponent(query)}`)
          .then((response) => response.json())
          .then((data) => {
            resultsList.innerHTML = '';
            
            //if results => display a list of links
            if (data.length > 0) {
              searchResults.style.display = 'block';
  
              data.forEach((result) => {
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.href = resultTemplate.href(result);
                link.textContent = resultTemplate.text(result);
                item.appendChild(link);
                resultsList.appendChild(item);
              });
            } else {
              searchResults.style.display = 'none';
            }
          });
      } else {
        searchResults.style.display = 'none';
      }
    });
  
    searchInput.addEventListener('blur', () => {
      setTimeout(() => {
        searchResults.style.display = 'none';
      }, 200);
    });
  }
  
  // Usage for posts
  search('search-input', 'search-results', '/posts/search/ajax', {
    href: (result) => `/post/${result.slug}`,
    text: (result) => `${result.title} - ${result.author}`,
  });
  
  // Usage for products
  search('search-input', 'search-results', '/store/search/ajax', {
    href: (result) => `/product/${result.slug}`,
    text: (result) => `${result.name}`,
  });