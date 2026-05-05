@extends('auth.auth') @section('content')
    <form action="{{ route('login.post') }}" method="POST" ...>
        <form action="{{ route('login.post') }}" method="POST" autocomplete="off" novalidate>

            @csrf @if ($errors->any())
                <div class="alert alert-danger">

                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach

                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username"
                    value="{{ old('username') }}" required autofocus>
            </div>
            <div class="mb-2">
                <label class="form-label">
                    Password
                </label>
                <div class="input-group input-group-flat">
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password"
                        autocomplete="off" required>
                </div>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </div>
        </form>
    </form>
@endsection
