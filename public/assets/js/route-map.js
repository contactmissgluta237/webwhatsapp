/**
 * RouteMap - A class to handle Google Maps route visualization
 */
class RouteMap {
    /**
     * Initialize the RouteMap
     * @param {Object} config - Configuration object
     */
    constructor(config = {}) {
        // Map elements
        this.map = null;
        this.directionsService = null;
        this.directionsRenderer = null;
        this.markers = [];

        // Route points
        this.origin = config.origin || null;
        this.destination = config.destination || null;

        // UI elements
        this.mapElementId = config.mapElementId || "map";
        this.distanceElementId = config.distanceElementId || "distance-text";
        this.durationElementId = config.durationElementId || "duration-text";

        // Status flags
        this.isInitialized = false;

        // Bind methods
        this._handleWindowResize = this._handleWindowResize.bind(this);
    }

    /**
     * Initialize Google Maps and direction services
     */
    initialize() {
        // Initializing Google Maps

        if (this.isInitialized) return;

        try {
            // Center point between origin and destination
            const center = {
                lat: (this.origin.lat + this.destination.lat) / 2,
                lng: (this.origin.lng + this.destination.lng) / 2,
            };

            // Initialize map
            this.map = new google.maps.Map(
                document.getElementById(this.mapElementId),
                {
                    zoom: 12,
                    center: center,
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true,
                },
            );

            // Initialize directions service
            this.directionsService = new google.maps.DirectionsService();
            this.directionsRenderer = new google.maps.DirectionsRenderer({
                map: this.map,
                suppressMarkers: true, // Hide default markers, we'll add custom ones
                polylineOptions: {
                    strokeColor: "#0d6efd",
                    strokeWeight: 5,
                    strokeOpacity: 0.7,
                },
            });

            this.isInitialized = true;

            // Calculate route
            this.calculateRoute();

            // Add window resize listener
            window.addEventListener("resize", this._handleWindowResize);
        } catch (error) {
            this._handleError("initialization", error);
        }
    }

    /**
     * Calculate and display route between origin and destination
     */
    calculateRoute() {
        if (!this.isInitialized) {
            console.error("Map not initialized yet");
            return;
        }

        // Calculating route
        this._updateUI("loading");

        const request = {
            origin: { lat: this.origin.lat, lng: this.origin.lng },
            destination: { lat: this.destination.lat, lng: this.destination.lng },
            travelMode: google.maps.TravelMode.DRIVING,
            unitSystem: google.maps.UnitSystem.METRIC,
            optimizeWaypoints: true,
        };

        this.directionsService.route(request, (result, status) => {
            if (status === "OK") {
                // Route calculated successfully

                // Display the route
                this._displayRoute(result);
            } else {
                this._handleError(
                    "routing",
                    new Error(`Routing failed: ${status}`),
                );
            }
        });
    }

    /**
     * Clean up resources when the component is destroyed
     */
    destroy() {
        window.removeEventListener("resize", this._handleWindowResize);
        this._clearMarkers();
        this.map = null;
        this.directionsService = null;
        this.directionsRenderer = null;
        this.isInitialized = false;
    }

    /**
     * Display route on the map and update UI
     * @private
     * @param {Object} routeData - Route data to display
     */
    _displayRoute(routeData) {
        // Display on map
        this.directionsRenderer.setDirections(routeData);

        // Extract information
        const route = routeData.routes[0];
        if (!route || !route.legs || !route.legs[0]) {
            this._handleError("display", new Error("Invalid route data"));
            return;
        }

        const leg = route.legs[0];

        // Update UI
        this._updateUI("success", {
            distance: leg.distance.text,
            duration: leg.duration.text,
        });

        // Clear existing markers
        this._clearMarkers();

        // Add custom markers with names
        this._addCustomMarker(
            { lat: this.origin.lat, lng: this.origin.lng },
            this.origin.name || "Departure",
            "origin"
        );
        
        this._addCustomMarker(
            { lat: this.destination.lat, lng: this.destination.lng },
            this.destination.name || "Destination",
            "destination"
        );

        // Adjust map view
        const bounds = new google.maps.LatLngBounds();
        bounds.extend(new google.maps.LatLng(this.origin.lat, this.origin.lng));
        bounds.extend(
            new google.maps.LatLng(this.destination.lat, this.destination.lng),
        );
        this.map.fitBounds(bounds);
    }

    /**
     * Add a custom marker with a label showing the location name
     * @private
     * @param {Object} position - Position for the marker
     * @param {string} name - Name to display
     * @param {string} type - Type of marker (origin or destination)
     */
    _addCustomMarker(position, name, type) {
        const icon = {
            url: type === 'origin' 
                ? 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                : 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
            scaledSize: new google.maps.Size(35, 35),
        };

        // Create the marker
        const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            icon: icon,
            title: name,
            animation: google.maps.Animation.DROP
        });

        // Create an info window with the name
        const infoWindow = new google.maps.InfoWindow({
            content: `<div style="font-weight: bold;">${name}</div>`,
            pixelOffset: new google.maps.Size(0, -5)
        });

        // Show the info window by default
        infoWindow.open(this.map, marker);

        // Add click event to toggle info window
        marker.addListener('click', () => {
            infoWindow.open(this.map, marker);
        });

        // Store the marker and infoWindow for cleanup
        this.markers.push({ marker, infoWindow });
    }

    /**
     * Clear all markers from the map
     * @private
     */
    _clearMarkers() {
        for (const item of this.markers) {
            item.marker.setMap(null);
            item.infoWindow.close();
        }
        this.markers = [];
    }

    /**
     * Handle window resize event
     * @private
     */
    _handleWindowResize() {
        if (!this.map) return;

        setTimeout(() => {
            google.maps.event.trigger(this.map, "resize");
        }, 100);
    }

    /**
     * Handle errors
     * @private
     * @param {string} context - Error context
     * @param {Error} error - Error object
     */
    _handleError(context, error) {
        console.error(`❌ Erreur pendant ${context}:`, error);

        const errorMessages = {
            ZERO_RESULTS: "Aucune route trouvée",
            OVER_QUERY_LIMIT: "Quota d'API dépassé",
            REQUEST_DENIED: "Requête refusée",
            INVALID_REQUEST: "Requête invalide",
            UNKNOWN_ERROR: "Erreur du Serveur",
        };

        let errorMsg = "Une erreur s'est produite";
        if (error.message) {
            const status = error.message.split(":").pop().trim();
            errorMsg = errorMessages[status] || error.message;
        }

        this._updateUI("error", { message: errorMsg });
    }

    /**
     * Update UI elements based on state
     * @private
     * @param {string} state - UI state (loading, success, or error)
     * @param {Object} data - Data to display
     */
    _updateUI(state, data = {}) {
        const distanceElement = document.getElementById(this.distanceElementId);
        const durationElement = document.getElementById(this.durationElementId);

        if (!distanceElement || !durationElement) return;

        switch (state) {
            case "loading":
                distanceElement.innerHTML =
                    '<span class="loading-status">Calcul en cours...</span>';
                durationElement.innerHTML =
                    '<span class="loading-status">Calcul en cours....</span>';
                break;

            case "success":
                distanceElement.innerHTML = `<span class="success-status">${data.distance}</span>`;
                durationElement.innerHTML = `<span class="success-status">${data.duration}</span>`;
                break;

            case "error":
                distanceElement.innerHTML = `<span class="error-status">${data.message}</span>`;
                durationElement.innerHTML = `<span class="error-status">${data.message}</span>`;
                break;
        }
    }
}

// Global error handling
window.addEventListener("error", (e) => {
    console.error("❌ Erreur JavaScript:", e.error);
});
