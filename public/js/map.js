let map;
let propertyData;
const urlParams = new URLSearchParams(window.location.search);
const propertyId = urlParams.get('id');

if (!propertyId) {
    showError('Property ID is required. Please provide an ID in the URL.');
} else {
    loadPropertyData();
}

async function loadPropertyData() {
    try {
        const response = await fetch(`/api/property.php?id=${propertyId}`);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Failed to load property data');
        }

        propertyData = data;
        displayPropertyInfo();
        displayNotes();
        initMap();
        
        document.getElementById('loading').style.display = 'none';
        document.getElementById('content').style.display = 'block';
    } catch (error) {
        showError(error.message);
    }
}

function displayPropertyInfo() {
    const property = propertyData.property;
    const nominatim = property.nominatim_data ? JSON.parse(property.nominatim_data) : {};
    
    document.getElementById('property-name').textContent = property.name;
    document.getElementById('property-address').textContent = property.address;
    document.getElementById('property-display-name').textContent = nominatim.display_name || 'N/A';
    document.getElementById('property-coords').textContent = 
        `${property.latitude}, ${property.longitude}`;
    document.getElementById('property-addresstype').textContent = nominatim.addresstype || 'N/A';
    document.getElementById('property-class').textContent = nominatim.class || 'N/A';
    document.getElementById('property-place-rank').textContent = nominatim.place_rank || 'N/A';
}

function displayNotes() {
    const notesList = document.getElementById('notes-list');
    const notes = propertyData.notes;

    if (notes.length === 0) {
        notesList.innerHTML = '<p style="color: #999;">No notes yet.</p>';
        return;
    }

    notesList.innerHTML = notes.map(note => `
        <div class="note">
            <p>${note.note}</p>
            <p class="note-date">${new Date(note.created_at).toLocaleString()}</p>
        </div>
    `).join('');
}

function initMap() {
    const property = propertyData.property;
    const nominatim = property.nominatim_data ? JSON.parse(property.nominatim_data) : {};
    const lat = parseFloat(property.latitude);
    const lng = parseFloat(property.longitude);

    map = L.map('map').setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Force map to recalculate size after initialization
    setTimeout(() => {
        map.invalidateSize();
    }, 100);

    const popupContent = `
        <div style="min-width: 280px; max-width: 350px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <h3 style="margin: 0 0 12px 0; padding-bottom: 8px; border-bottom: 2px solid #667eea; color: #333; font-size: 16px;">
                ${property.name}
            </h3>
            <div style="margin-bottom: 10px;">
                <strong style="color: #666; font-size: 12px;">📍 Location</strong>
                <p style="margin: 4px 0 0 0; color: #333; font-size: 13px; line-height: 1.4;">${nominatim.display_name || property.address}</p>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                <div>
                    <strong style="color: #666; font-size: 11px;">Type</strong>
                    <p style="margin: 2px 0 0 0; color: #333; font-size: 12px;">${nominatim.addresstype || 'N/A'}</p>
                </div>
                <div>
                    <strong style="color: #666; font-size: 11px;">Class</strong>
                    <p style="margin: 2px 0 0 0; color: #333; font-size: 12px;">${nominatim.class || 'N/A'}</p>
                </div>
                <div>
                    <strong style="color: #666; font-size: 11px;">Place Rank</strong>
                    <p style="margin: 2px 0 0 0; color: #333; font-size: 12px;">${nominatim.place_rank || 'N/A'}</p>
                </div>
                <div>
                    <strong style="color: #666; font-size: 11px;">Coordinates</strong>
                    <p style="margin: 2px 0 0 0; color: #333; font-size: 12px;">${lat.toFixed(5)}, ${lng.toFixed(5)}</p>
                </div>
            </div>
        </div>
    `;

    const marker = L.marker([lat, lng])
        .addTo(map)
        .bindPopup(popupContent, {
            maxWidth: 350,
            minWidth: 280
        });
    
    // Auto-open popup after 2 seconds
    setTimeout(() => {
        marker.openPopup();
    }, 500);
}

async function addNote() {
    const noteInput = document.getElementById('note-input');
    const noteText = noteInput.value.trim();

    if (!noteText) {
        alert('Please enter a note');
        return;
    }

    try {
        const response = await fetch('/api/add_note.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                property_id: propertyId,
                note: noteText
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Failed to add note');
        }

        noteInput.value = '';
        // Reload only the notes, not the entire property data
        await reloadNotes();
    } catch (error) {
        alert('Error adding note: ' + error.message);
    }
}

async function reloadNotes() {
    try {
        const response = await fetch(`/api/property.php?id=${propertyId}`);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Failed to load notes');
        }

        propertyData.notes = data.notes;
        displayNotes();
    } catch (error) {
        alert('Error reloading notes: ' + error.message);
    }
}

function showError(message) {
    document.getElementById('loading').style.display = 'none';
    const errorEl = document.getElementById('error-message');
    errorEl.textContent = message;
    errorEl.style.display = 'block';
}
