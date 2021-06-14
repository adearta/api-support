<body style="text-align: center;">
    <h2>Hello {{ $student[0]->name }},</h2>
    <h4>Anda mendapatkan undangan untuk mengikuti Webinar {{ $webinar[0]->event_name }}, pada:</h4>

    <h5>Tanggal: {{ $webinar[0]->event_date }} </h5>
    <h5>Jam: {{ $webinar[0]->event_time }} </h5>
    <h5>Zoom Link: {{ $webinar[0]->zoom_link }} </h5>

    <img style="width: 200px; height: 200px;"
        src="{{ $message->embed(env("WEBINAR_URL") . $webinar[0]->event_picture) }}">

    <br>

    <h5>Terima Kasih</h5>
</body>