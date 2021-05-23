<body style="text-align: center;">
    <h2>Hello {{ $data[0]->name }},</h2>
    <h4>Diingatkan kembali bahwa Webinar {{ $data[0]->event_name }} akan dilaksanakan h-{{ $reminder }} dari
        sekarang, yaitu pada:</h4>

    <h5>Tanggal: {{ $data[0]->event_date }} </h5>
    <h5>Jam: {{ $data[0]->event_time }} </h5>

    <img style="width: 200px; height: 200px;"
        src="{{ $message->embed("https://berenyisoft.eu/wp-content/uploads/2020/04/BSys-Smile.jpg") }}">

    <br>

    <h5>Terima Kasih</h5>
</body>