<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordKeeper</title>

    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ url('/') }}" class="logo">WordKeeper</a>

            <nav class="header-actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Log Out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-secondary">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Sign up</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="hero">
        <div class="container hero-inner">
            <section class="hero-content">
                <h1 class="hero-title">
                    Your Personal Foreign<br>
                    Word Dictionary
                </h1>

                <p class="hero-description">
                    Build and organize your vocabulary across multiple languages.
                    Track words, meanings, and examples in one simple place.
                </p>

                <div class="hero-actions">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-large">Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-primary btn-large">Sign up</a>
                        <a href="{{ route('login') }}" class="btn btn-secondary btn-large">Log in</a>
                    @endauth
                </div>
            </section>

            <section class="hero-image-wrapper">
                <img
                    src="{{ asset('images/welcome-book.jpg') }}"
                    alt="Open dictionary book"
                    class="hero-image"
                >
            </section>
        </div>
    </main>
</body>
</html>
