@extends('layout')
@section('title', 'Аватар')
@section('content')
<div class="form-frame">
    <header class="page-head"><div><h1>Аватар<span>.</span></h1></div></header>
    <form class="panel section-panel stack" action="{{ route('avatar.update') }}" method="post" enctype="multipart/form-data">
        @csrf
        <label>Изображение<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
            <small>JPG, PNG или WebP, до 2 МБ.</small>
        </label>
        <div><button class="button primary">Сохранить аватар</button></div>
    </form>
</div>
@endsection
