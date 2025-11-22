const form = document.getElementById('propertyForm');
const messageEl = document.getElementById('message');
const loadingEl = document.getElementById('loading');
const resultEl = document.getElementById('result');

// Load recent properties on page load
loadRecentProperties();

async function loadRecentProperties() {
    try {
        const response = await fetch('/api/recent_properties.php');
        const data = await response.json();

        const listEl = document.getElementById('recentProperties');

        if (!data.success || data.properties.length === 0) {
            listEl.innerHTML = '<li class="no-properties">No properties yet</li>';
            return;
        }

        listEl.innerHTML = data.properties.map(prop => {
            const date = new Date(prop.created_at).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            });
            return `
                <li class="property-item" onclick="window.location.href='/public/map.html?id=${prop.id}'">
                    <h3>${escapeHtml(prop.name)}</h3>
                    <p>${escapeHtml(prop.address)}</p>
                    <p class="date">${date}</p>
                </li>
            `;
        }).join('');
    } catch (error) {
        console.error('Failed to load recent properties:', error);
        document.getElementById('recentProperties').innerHTML = 
            '<li class="no-properties">Failed to load properties</li>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const name = document.getElementById('name').value.trim();
    const address = document.getElementById('address').value.trim();

    if (!name || !address) {
        showMessage('Please fill in all fields', 'error');
        return;
    }

    messageEl.style.display = 'none';
    resultEl.style.display = 'none';
    loadingEl.style.display = 'block';
    form.querySelector('button').disabled = true;

    try {
        // Call geolocation API (OpenStreetMap Nominatim)
        const geoResponse = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&addressdetails=1&limit=5`
        );
        const geoData = await geoResponse.json();

        if (!geoData || geoData.length === 0) {
            // Try a second search with more flexible parameters
            const fallbackResponse = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&addressdetails=1&limit=5&accept-language=en`
            );
            const fallbackData = await fallbackResponse.json();
            
            if (!fallbackData || fallbackData.length === 0) {
                throw new Error(
                    'Address not found. Please try:\n' +
                    '• Adding more details (street number, city, state, country)\n' +
                    '• Using a different format (e.g., "123 Main St, New York, NY, USA")\n' +
                    '• Checking for typos in the address'
                );
            }
            
            // Use fallback data
            const location = fallbackData[0];
            const latitude = parseFloat(location.lat);
            const longitude = parseFloat(location.lon);
            
            await saveProperty(name, address, latitude, longitude, location);
            return;
        }

        const location = geoData[0];
        const latitude = parseFloat(location.lat);
        const longitude = parseFloat(location.lon);

        await saveProperty(name, address, latitude, longitude, location);
    } catch (error) {
        showMessage(error.message, 'error');
    } finally {
        loadingEl.style.display = 'none';
        form.querySelector('button').disabled = false;
    }
});

async function saveProperty(name, address, latitude, longitude, location) {
    try {
        // Save to database
        const saveResponse = await fetch('/api/save_property.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name,
                address,
                latitude,
                longitude,
                nominatim_data: location
            })
        });

        const saveData = await saveResponse.json();

        if (!saveResponse.ok) {
            throw new Error(saveData.error || 'Failed to save property');
        }

        showSuccess(saveData);
        // Reload recent properties list
        loadRecentProperties();
    } catch (error) {
        throw error;
    }
}

function showMessage(text, type) {
    messageEl.textContent = text;
    messageEl.className = `message ${type}`;
    messageEl.style.display = 'block';
}

function showSuccess(data) {
    const property = data.property;
    const nominatim = property.nominatim_data ? JSON.parse(property.nominatim_data) : {};
    resultEl.innerHTML = `
        <h3>✓ Property Added Successfully!</h3>
        <p><strong>Name:</strong> ${property.name}</p>
        <p><strong>Address:</strong> ${property.address}</p>
        <p><strong>Display Name:</strong> ${nominatim.display_name || 'N/A'}</p>
        <p><strong>Latitude:</strong> ${property.latitude}</p>
        <p><strong>Longitude:</strong> ${property.longitude}</p>
        <p><strong>Type:</strong> ${nominatim.addresstype || 'N/A'}</p>
        <p><strong>Class:</strong> ${nominatim.class || 'N/A'}</p>
        <a href="/public/map.html?id=${property.id}">View on Map →</a>
    `;
    resultEl.style.display = 'block';
    form.reset();
}
