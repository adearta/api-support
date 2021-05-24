<body style="text-align: center;">
    <h2>Hello {{ $school[0]->name }},</h2>
    <h4>Anda mendapatkan undangan untuk mengikuti Webinar {{ $webinar['event_name'] }}, pada:</h4>

    <h5>Tanggal: {{ $webinar['event_date'] }} </h5>
    <h5>Jam: {{ $webinar['event_time'] }} </h5>
    <h5>Link to the student page</h5>

    <img style="width: 200px; height: 200px;"
        src="{{ $message->embed("https://independensi.com/wp-content/uploads/2018/03/180330-Pancasila-820x510.jpg") }}">

    <br>

    <h5>Terima Kasih</h5>
</body>