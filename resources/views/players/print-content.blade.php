<h1>{{ $title }}</h1>
@foreach($sections as $section)
    <h2>{{ $section['heading'] }}</h2>
    <dl>
        @foreach($section['rows'] as $row)
            <dt>{{ $row['label'] }}</dt>
            <dd>{{ $row['value'] ?? '—' }}</dd>
            @if($row['clarification'] !== null)<dd class="print-note">Уточнение: {{ $row['clarification'] }}</dd>@endif
        @endforeach
    </dl>
@endforeach
