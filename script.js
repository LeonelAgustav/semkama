document.addEventListener('DOMContentLoaded', () => {
    // Load default page (login.html)
    loadPage('../php/login.php');

    // Handle navigation between pages
    document.addEventListener('click', (event) => {
        if (event.target.tagName === 'A') {
            event.preventDefault();
            const page = event.target.getAttribute('href');
            loadPage(`../php/${page}`);
        }
    });
});

// Function to load an external HTML page into #page-content
function loadPage(url) {
    fetch(url)
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Could not load ${url}`);
            }
            return response.text();
        })
        .then((html) => {
            document.getElementById('page-content').innerHTML = html;
        })
        .catch((error) => {
            console.error(error);
            document.getElementById('page-content').innerHTML = '<p>Error loading page.</p>';
        });
}
