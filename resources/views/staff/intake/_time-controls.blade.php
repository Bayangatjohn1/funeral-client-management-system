<div class="time-editor-fields">
    <label>Hour<input type="number" min="1" max="12" inputmode="numeric" data-time-editor="hour" data-skip-autocap placeholder="08"></label>
    <span class="time-editor-colon" aria-hidden="true">:</span>
    <label>Minute<input type="number" min="0" max="59" inputmode="numeric" data-time-editor="minute" data-skip-autocap placeholder="30"></label>
</div>
<div class="time-period-buttons" aria-label="AM or PM">
    <button type="button" data-time-period="AM" aria-pressed="false">AM</button>
    <button type="button" data-time-period="PM" aria-pressed="false">PM</button>
</div>
<div class="time-minute-shortcuts" aria-label="Quick minute choices">
    @foreach(['00', '15', '30', '45'] as $minute)
        <button type="button" data-time-minute="{{ $minute }}">:{{ $minute }}</button>
    @endforeach
</div>
<p class="time-editor-error" role="alert" hidden>Enter an hour from 1–12, a minute from 00–59, and choose AM or PM.</p>
<button type="button" class="time-editor-apply" data-time-apply>Apply time</button>
