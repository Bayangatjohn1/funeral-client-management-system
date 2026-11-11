@props(['field'])
@foreach($errors->get($field) as $message)
    <p class="form-error" role="alert" data-validation-for="{{ $field }}">{{ $message }}</p>
@endforeach
