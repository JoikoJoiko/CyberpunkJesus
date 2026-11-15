@if($errors->any())
    <div class="notice error" role="alert">
        <strong>Проверьте форму</strong>
        <ul>
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
