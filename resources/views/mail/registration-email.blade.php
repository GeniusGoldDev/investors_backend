@component('mail::message')
# Registration Notification

Hi Admin, 
<p>A user has just registered and successfully login to the {{ config('app.name')}} platform.</p>
<p>Below are the user's information.</p>
<div>
   <p class="mb-0"> <b>Fullname:</b> {{$data["fname"] . " ". $data["lname"]}}</p>
   <p class="mb-0"> <b>Email:</b> {{$data["email"]}}</p>
   <p class="mb-0"> <b>Phone number:</b> {{$data["phone"]}}</p>
</div>



{{ config('app.name') }}
@endcomponent
