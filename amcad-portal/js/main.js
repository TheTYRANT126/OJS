/**
 * JavaScript principal del Portal AMCAD
 */

document.addEventListener('DOMContentLoaded', function() {
    // =============================
    // Sticky Header con efecto scroll
    // =============================
    const header = document.getElementById('mainHeader');
    let lastScroll = 0;

    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

        lastScroll = currentScroll;
    });

    // =============================
    // Menú móvil
    // =============================
    const menuToggles = document.querySelectorAll('.menu-toggle');

    menuToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const headerEl = this.closest('header');
            const nav = headerEl ? headerEl.querySelector('.main-nav') : null;
            if (!nav) return;

            const isOpen = nav.classList.toggle('is-open');
            this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

	document.querySelectorAll('.main-nav a').forEach(link => {
		link.addEventListener('click', function() {
			const headerEl = this.closest('header');
			const nav = headerEl ? headerEl.querySelector('.main-nav') : null;
			const toggle = headerEl ? headerEl.querySelector('.menu-toggle') : null;
			if (nav) nav.classList.remove('is-open');
			if (toggle) toggle.setAttribute('aria-expanded', 'false');
		});
	});

	// =============================
	// Menú de usuario en el header
	// =============================
	const userMenus = document.querySelectorAll('.header-user-menu');

	if (userMenus.length) {
		const closeAllUserMenus = (exception = null) => {
			userMenus.forEach(menu => {
				if (exception && menu === exception) return;
				menu.classList.remove('is-open');
				const trigger = menu.querySelector('.user-menu-trigger');
				if (trigger) {
					trigger.setAttribute('aria-expanded', 'false');
				}
			});
		};

		userMenus.forEach(menu => {
			const trigger = menu.querySelector('.user-menu-trigger');
			if (!trigger) return;

			trigger.addEventListener('click', event => {
				event.preventDefault();
				const isOpen = menu.classList.toggle('is-open');
				trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

				if (isOpen) {
					closeAllUserMenus(menu);
				}
			});
		});

		document.addEventListener('click', event => {
			const clickedInsideMenu = Array.from(userMenus).some(menu => menu.contains(event.target));
			if (!clickedInsideMenu) {
				closeAllUserMenus();
			}
		});

		document.addEventListener('keydown', event => {
			if (event.key === 'Escape') {
				closeAllUserMenus();
			}
		});
	}

	document.querySelectorAll('.user-name-link').forEach(link => {
		link.addEventListener('click', event => {
			event.stopPropagation();
			const menu = link.closest('.header-user-menu');
			if (menu) {
				menu.classList.remove('is-open');
				const trigger = menu.querySelector('.user-menu-trigger');
				if (trigger) {
					trigger.setAttribute('aria-expanded', 'false');
				}
			}
			const targetUrl = link.getAttribute('data-dashboard-url');
			if (targetUrl) {
				window.location.href = targetUrl;
			}
		});
	});

	// =============================
	// Toggle entre Revista/Artículo
	// =============================
    const searchTypeBtns = document.querySelectorAll('.search-type-btn');
    const searchTypeInput = document.getElementById('searchType');

    searchTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remover active de todos
            searchTypeBtns.forEach(b => b.classList.remove('active'));

            // Agregar active al clickeado
            this.classList.add('active');

            // Actualizar el input hidden
            const type = this.getAttribute('data-type');
            if (searchTypeInput) {
                searchTypeInput.value = type;
            }
        });
    });

    // =============================
    // Toggle Búsqueda Avanzada
    // =============================
    const advancedSearchToggle = document.getElementById('advancedSearchToggle');
    const advancedSearchForm = document.getElementById('advancedSearchForm');

    if (advancedSearchToggle && advancedSearchForm) {
        advancedSearchToggle.addEventListener('click', function(e) {
            e.preventDefault();

            if (advancedSearchForm.style.display === 'none' || advancedSearchForm.style.display === '') {
                advancedSearchForm.style.display = 'block';
                this.textContent = this.textContent.replace('avanzada', 'simple').replace('Advanced', 'Simple');
            } else {
                advancedSearchForm.style.display = 'none';
                this.textContent = this.textContent.replace('simple', 'avanzada').replace('Simple', 'Advanced');
            }
        });
    }

    // =============================
    // Filtro por Categoría
    // =============================
    const categoryFilter = document.getElementById('categoryFilter');
    const allJournalsGrid = document.getElementById('allJournalsGrid');

    if (categoryFilter && allJournalsGrid) {
        categoryFilter.addEventListener('change', function() {
            const selectedCategory = this.value;
            const journalCards = allJournalsGrid.querySelectorAll('.journal-card');

            journalCards.forEach(card => {
                const cardCategory = card.getAttribute('data-category') || '';
                const categoryList = cardCategory
                    .split(',')
                    .map(value => value.trim())
                    .filter(Boolean);

                if (selectedCategory === '' || categoryList.includes(selectedCategory)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // =============================
    // Animación de aparición de tarjetas
    // =============================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    entry.target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);

                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const journalCards = document.querySelectorAll('.journal-card');
    journalCards.forEach(card => {
        observer.observe(card);
    });

    // =============================
    // Validación de formulario de búsqueda avanzada
    // =============================
    const advancedForm = advancedSearchForm?.querySelector('form');

    if (advancedForm) {
        advancedForm.addEventListener('submit', function(e) {
            // Validar que al menos un campo esté lleno
            const inputs = this.querySelectorAll('input[type="text"], select');
            let hasValue = false;

            inputs.forEach(input => {
                if (input.value.trim() !== '') {
                    hasValue = true;
                }
            });

            if (!hasValue) {
                e.preventDefault();
                alert('Por favor ingresa al menos un criterio de búsqueda.');
                return;
            }

            const yearInputs = this.querySelectorAll('input[name="date_from"], input[name="date_to"]');
            for (const input of yearInputs) {
                const value = input.value.trim();
                if (value !== '' && !/^\d{4}$/.test(value)) {
                    e.preventDefault();
                    alert('Por favor ingresa un año válido de 4 dígitos.');
                    input.focus();
                    return;
                }
            }
        });
    }

    // =============================
    // Smooth scroll para enlaces internos
    // =============================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');

            // Solo aplicar smooth scroll si no es solo "#"
            if (href !== '#') {
                e.preventDefault();

                const target = document.querySelector(href);
                if (target) {
                    const headerHeight = header.offsetHeight;
                    const targetPosition = target.offsetTop - headerHeight;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // =============================
    // Mejorar accesibilidad de botones en tarjetas
    // =============================
    const journalButtons = document.querySelectorAll('.btn-view-journal, .btn-latest-pub');

    journalButtons.forEach(btn => {
        btn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    const footers = document.querySelectorAll('.main-footer, .amcad-footer');
    const defaultDevCredit = 'Desarrollado por Emmanuel Velásquez Ortiz 😉';

    footers.forEach(footer => {
        const footerCenter = footer.querySelector('.footer-center') || footer.querySelector('.footer-content') || footer;
        let devCredit = footer.querySelector('.dev-credit');
        let footerClicks = 0;
        let footerTimer = null;
        let devCreditTimer = null;

        if (!devCredit) {
            devCredit = document.createElement('p');
            devCredit.className = 'dev-credit is-hidden';
            devCredit.textContent = footer.getAttribute('data-dev-credit') || defaultDevCredit;
            footerCenter.appendChild(devCredit);
        }

        footer.addEventListener('click', function() {
            footerClicks += 1;
            clearTimeout(footerTimer);

            if (footerClicks >= 6) {
                devCredit.classList.add('is-visible');
                devCredit.classList.remove('is-hidden');
                footerClicks = 0;
                clearTimeout(devCreditTimer);
                devCreditTimer = setTimeout(() => {
                    devCredit.classList.remove('is-visible');
                    devCredit.classList.add('is-hidden');
                }, 5000);
                return;
            }

            footerTimer = setTimeout(() => {
                footerClicks = 0;
            }, 1200);
        });
    });

    // =============================
    // Página de Búsqueda
    // =============================
    if (document.querySelector('.search-results-container')) {

        // Toggle de filtros específicos para artículos
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const articleSpecificFilters = document.querySelector('.filter-article-specific');

        typeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (articleSpecificFilters) {
                    if (this.value === 'articulo') {
                        articleSpecificFilters.style.display = 'block';
                    } else {
                        articleSpecificFilters.style.display = 'none';
                    }
                }
            });
        });

        // Validación de rango de años
        const yearInputs = document.querySelectorAll('input[name="date_from"], input[name="date_to"]');
        yearInputs.forEach(input => {
            input.addEventListener('input', function() {
                // Solo permitir números
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            input.addEventListener('blur', function() {
                const value = this.value.trim();
                if (value !== '' && !/^\d{4}$/.test(value)) {
                    alert('Por favor ingresa un año válido de 4 dígitos.');
                    this.focus();
                }
            });
        });

        // Validación del formulario de refinamiento
        const refineForm = document.getElementById('refineForm');
        if (refineForm) {
            refineForm.addEventListener('submit', function(e) {
                // Validar que al menos un campo tenga valor
                const inputs = this.querySelectorAll('input[type="text"], select');
                let hasValue = false;

                inputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        hasValue = true;
                    }
                });

                // Si no hay ningún valor, prevenir submit
                if (!hasValue) {
                    e.preventDefault();
                    alert('Por favor ingresa al menos un criterio de búsqueda.');
                    return;
                }

                // Validar años si están presentes
                const dateFrom = this.querySelector('input[name="date_from"]');
                const dateTo = this.querySelector('input[name="date_to"]');

                if (dateFrom && dateFrom.value.trim() !== '' && !/^\d{4}$/.test(dateFrom.value)) {
                    e.preventDefault();
                    alert('El año "Desde" debe ser de 4 dígitos.');
                    dateFrom.focus();
                    return;
                }

                if (dateTo && dateTo.value.trim() !== '' && !/^\d{4}$/.test(dateTo.value)) {
                    e.preventDefault();
                    alert('El año "Hasta" debe ser de 4 dígitos.');
                    dateTo.focus();
                    return;
                }

                // Validar que date_from <= date_to
                if (dateFrom && dateTo && dateFrom.value && dateTo.value) {
                    const yearFrom = parseInt(dateFrom.value);
                    const yearTo = parseInt(dateTo.value);

                    if (yearFrom > yearTo) {
                        e.preventDefault();
                        alert('El año "Desde" no puede ser mayor que el año "Hasta".');
                        dateFrom.focus();
                        return;
                    }
                }
            });
        }

        // Accesibilidad para botones de tabla
        const tableButtons = document.querySelectorAll('.btn-table-action');
        tableButtons.forEach(btn => {
            btn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    }
});

// =============================
// Función auxiliar para cambiar idioma
// =============================
function changeLanguage(lang) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('lang', lang);
    window.location.href = currentUrl.toString();
}

// =============================
// Manejo de errores de carga de imágenes
// =============================
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img');

    images.forEach(img => {
        img.addEventListener('error', function() {
            // Si la imagen falla al cargar, mostrar placeholder
            if (this.classList.contains('banner-logo') ||
                this.classList.contains('ojs-logo') ||
                this.classList.contains('footer-logo')) {
                this.style.display = 'none';
            }
        });
    });
});
