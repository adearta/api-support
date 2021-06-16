<body style="text-align: center;">
    <h2>Hello {{ $school[0]->name }},</h2>
    <h4>Anda mendapatkan undangan untuk mengikuti Webinar {{ $webinar['event_name'] }}, pada:</h4>

    <h5>Tanggal: {{ $webinar['event_date'] }} </h5>
    <h5>Jam: {{ $webinar['event_time'] }} </h5>
    <h5>Link to the student page</h5>

    <img style="width: 200px; height: 200px;"
        src="{{ $message->embed(env("WEBINAR_URL") . $webinar[0]->event_picture) }}">
        {{-- src="https://images.app.goo.gl/UQDxuKKcZGKt33B99"> --}}
    <br>

    <h5>Terima Kasih</h5>
</body>