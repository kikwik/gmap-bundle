document.addEventListener("DOMContentLoaded", function() {
    const autocompleteForms = document.querySelectorAll('.js-kw-gmap-autocomplete');
    autocompleteForms.forEach(function (autocompleteForm) {
        const autocomplete = new kwAutocomplete();
        autocomplete.init(autocompleteForm);
    });
})

class kwAutocomplete
{
    async init(autocompleteForm){
        await google.maps.importLibrary("places");

        this.inputs = {
            autocomplete:   autocompleteForm.querySelector('.js-autocomplete'),
            street:         autocompleteForm.querySelector('.js-street'),
            streetNumber:   autocompleteForm.querySelector('.js-streetNumber'),
            zipCode:        autocompleteForm.querySelector('.js-zipCode'),
            city:           autocompleteForm.querySelector('.js-city'),
            province:       autocompleteForm.querySelector('.js-province'),
            region:         autocompleteForm.querySelector('.js-region'),
            country:        autocompleteForm.querySelector('.js-country'),
            latitude:       autocompleteForm.querySelector('.js-latitude'),
            longitude:      autocompleteForm.querySelector('.js-longitude')
        };

        const options = {
            componentRestrictions: { country: "it" },
            fields: ["address_components", "geometry"],
            strictBounds: false,
            types: ["geocode"],
        };
        this.autocomplete = new google.maps.places.Autocomplete(this.inputs.autocomplete, options);
        this.autocomplete.addListener("place_changed", () => {
            const place = this.autocomplete.getPlace();

            if(place.address_components)
            {
                for (const component of place.address_components)
                {
                    const componentType = component.types[0];
                    switch (componentType)
                    {
                        case "route": {
                            if(this.inputs.street) this.inputs.street.value = component.short_name;
                            break;
                        }
                        case "street_number": {
                            if(this.inputs.streetNumber) this.inputs.streetNumber.value = component.long_name;
                            break;
                        }
                        case "postal_code": {
                            if(this.inputs.zipCode) this.inputs.zipCode.value = component.long_name;
                            break;
                        }
                        case "locality":
                            if(this.inputs.city) this.inputs.city.value = component.long_name;
                            break;
                        case "administrative_area_level_2": {
                            if(this.inputs.province) this.inputs.province.value = component.short_name;
                            break;
                        }
                        case "administrative_area_level_1": {
                            if(this.inputs.region) this.inputs.region.value = component.long_name;
                            break;
                        }
                        case "country":
                            if(this.inputs.country) this.inputs.country.value = component.long_name;
                            break;
                    }
                }
            }

            if(place.geometry && place.geometry.location)
            {
                if(this.inputs.latitude) this.inputs.latitude.value = place.geometry.location.lat();
                if(this.inputs.longitude) this.inputs.longitude.value = place.geometry.location.lng();
            }

        });
    }

}