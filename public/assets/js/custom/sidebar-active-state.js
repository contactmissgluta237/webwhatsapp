/**
 * Manages sidebar menu active state handling for different URL patterns
 */
const initializeSidebarActiveState = () => {
    const cleanMenuState = () => {
        document.querySelectorAll('.main-nav li, .main-nav a').forEach(el => {
            el.classList.remove('active', 'show');
            if (el.hasAttribute('aria-expanded')) {
                el.setAttribute('aria-expanded', 'false');
            }
        });

        document.querySelectorAll('.collapse').forEach(collapse => {
            collapse.classList.remove('show');
            collapse.setAttribute('aria-expanded', 'false');
        });
    };

    const activateMenuElement = (exactMatch) => {
        const directParentLi = exactMatch.closest('li');
        if (directParentLi) {
            directParentLi.classList.add('active');
            exactMatch.classList.add('active');
        }

        const parentCollapse = exactMatch.closest('.collapse');
        if (parentCollapse) {
            parentCollapse.classList.add('show');
            parentCollapse.setAttribute('aria-expanded', 'true');

            const parentButton = document.querySelector(`[href="#${parentCollapse.id}"]`);
            if (parentButton) {
                parentButton.classList.add('active', 'show');
                parentButton.setAttribute('aria-expanded', 'true');
            }
        }
    };

    const handleSidebarState = () => {
        const currentPath = window.location.pathname;
        cleanMenuState();

        if (currentPath.endsWith('/create')) {
            // Find the exact link that matches the complete path
            const createLink = Array.from(document.querySelectorAll('.collapse a')).find(link => {
                const href = link.getAttribute('href').replace(window.location.origin, '');
                return href === currentPath;
            });

            if (createLink) {
                activateMenuElement(createLink);
            } else {
                // If we cannot find an exact match for a deep path like /bottles/types/create
                // We try to find the closest parent link
                const pathParts = currentPath.split('/').filter(part => part.length > 0);
                
                // For a path like /bottles/types/create, we try /bottles/types then /bottles
                for (let i = pathParts.length - 2; i >= 0; i--) {
                    const partialPath = '/' + pathParts.slice(0, i + 1).join('/');
                    
                    const parentLink = Array.from(document.querySelectorAll('.main-nav a')).find(link => {
                        const href = link.getAttribute('href').replace(window.location.origin, '');
                        return href === partialPath;
                    });
                    
                    if (parentLink) {
                        activateMenuElement(parentLink);
                        break;
                    }
                }
            }
            return;
        }

        const exactMatch = Array.from(document.querySelectorAll('.main-nav a')).find(link => {
            const href = link.getAttribute('href').replace(window.location.origin, '');
            return href === currentPath;
        });

        if (exactMatch) {
            activateMenuElement(exactMatch);
        }
    };

    setTimeout(handleSidebarState, 0);
};

document.addEventListener('DOMContentLoaded', initializeSidebarActiveState);