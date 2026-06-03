const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
fetch('/api/register', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
    },
    body: JSON.stringify(formObject),
});

document.getElementById('zip_code').addEventListener('input', async function () {
    const zipCode = this.value.trim();

    // Reference to the city dropdown
    const cityDropdown = document.getElementById('city_id');

    // Clear existing options and disable the dropdown initially
    cityDropdown.innerHTML = '<option value="">Select your city</option>';
    cityDropdown.disabled = true;

    // Ensure ZIP code is 5 digits before making the request
    if (zipCode.length === 5) {
        try {
            const response = await fetch(`/api/cities-by-zip/${zipCode}`);

            if (!response.ok) {
                throw new Error('Failed to fetch cities');
            }

            const data = await response.json();

            if (data.cities.length > 0) {
                // Populate the city dropdown with options
                data.cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.city_id;
                    option.textContent = `${city.city_name}, ${city.state}`;
                    cityDropdown.appendChild(option);
                });

                // Enable the dropdown
                cityDropdown.disabled = false;
            } else {
                alert('No cities found for the given ZIP code.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while fetching cities.');
        }
    }
});
document.getElementById('registrationForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const formObject = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/api/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(formObject),
        });

        if (response.ok) {
            const data = await response.json();
            alert('Registration successful!');
            console.log('User:', data.user);
        } else {
            const errorData = await response.json();
            alert('Registration failed: ' + JSON.stringify(errorData.errors));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An unexpected error occurred.');
    }
});

