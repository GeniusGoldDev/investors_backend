@component('mail::message')
# Property Notification

Hi {{$data["name"]}}, 
<p>A property was just assigned to You.</p>
<p>Below are the property's information.</p>
<div>
    <p class="mb-0"> <b>Property:</b> {{$data['request']->name}}</p>
   <p class="mb-0"> <b>Type:</b> {{$data["request"]->type}}</p>
   <p class="mb-0"> <b>Amount to be sold:</b> # {{ number_format($data['amount'], 2, '.', ',') }}</p>
</div>



{{ config('app.name') }}
@endcomponent
