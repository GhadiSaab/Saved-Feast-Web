<x-mail::message>
# New Restaurant Partner Application Received

A new application has been submitted through the SavedFeast website.

**Details:**

*   **Restaurant Name:** {{ $data['restaurant_name'] }}
*   **Address:** {{ $data['address'] }}
*   **Cuisine Type:** {{ $data['cuisine_type'] }}
*   **Contact Person:** {{ $data['contact_name'] }}
*   **Contact Email:** {{ $data['contact_email'] }}
*   **Contact Phone:** {{ $data['contact_phone'] }}

**Description/Reason:**
{{ $data['description'] ?? 'N/A' }}


Please review this application and follow up with the contact person.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
