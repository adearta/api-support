<body style="text-align: center;">
    <h2>Hello {{ $student[0]->name }},</h2>
    <h4>Diingatkan kembali bahwa Webinar {{ $event->event_name }} akan dilaksanakan h-{{ $reminder }} dari
        sekarang, yaitu pada:</h4>

    <h5>Tanggal: {{ $event->event_date }} </h5>
    <h5>Jam: {{ $event->event_time }} </h5>
    <h5>Zoom Link: {{ $event->zoom_link }}</h5>

    <img style="width: 200px; height: 200px;"
        src="{{ $message->embed("https://berenyisoft.eu/wp-content/uploads/2020/04/BSys-Smile.jpg") }}">

    <br>

    <h5>Terima Kasih</h5>
</body>