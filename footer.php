            </main>
        </div>
    </div>
    <script>
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const current = html.getAttribute('data-theme');
            const next = current === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });
    }

    function updateThemeIcon(theme) {
        if (!themeToggle) return;
        const icon = themeToggle.querySelector('i');
        const text = themeToggle.querySelector('span');
        if (theme === 'dark') {
            icon.className = 'fas fa-sun';
            if (text) text.textContent = 'Light';
        } else {
            icon.className = 'fas fa-moon';
            if (text) text.textContent = 'Dark';
        }
    }

    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }

    // Sidebar section collapse toggle
    document.querySelectorAll('.nav-section-title[data-toggle="collapse"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const section = btn.closest('.nav-section');
        if (section) section.classList.toggle('collapsed');
      });
    });

    // Quick Add Dropdown
    const quickAddBtn = document.getElementById('quickAddBtn');
    const quickAddMenu = document.getElementById('quickAddMenu');

    if (quickAddBtn && quickAddMenu) {
        quickAddBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            quickAddMenu.classList.toggle('active');
        });

        document.addEventListener('click', () => {
            quickAddMenu.classList.remove('active');
        });
    }

    // Global Search
    const globalSearch = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');

    if (globalSearch && searchResults) {
        let searchTimeout;

        // Keyboard Shortcut ⌘K or Ctrl+K
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                globalSearch.focus();
            }
            // Close search on escape
            if (e.key === 'Escape' && document.activeElement === globalSearch) {
                globalSearch.blur();
                searchResults.classList.remove('active');
            }
        });
        
        globalSearch.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = globalSearch.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.remove('active');
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    
                    if (data.length > 0) {
                        searchResults.innerHTML = data.map(item => `
                            <a href="${item.url}" class="search-result-item">
                                <i class="fas fa-${item.icon}"></i>
                                <div>
                                    <div class="title">${item.title}</div>
                                    <div class="subtitle">${item.subtitle}</div>
                                </div>
                            </a>
                        `).join('');
                        searchResults.classList.add('active');
                    } else {
                        searchResults.innerHTML = '<div class="search-result-item"><span class="text-muted">No results found</span></div>';
                        searchResults.classList.add('active');
                    }
                } catch (e) {
                    console.error('Search error:', e);
                }
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!globalSearch.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });
    }
    </script>
</body>
</html>
