@if (session('error'))
    <div role="alert" style="margin: 1rem auto; max-width: 40rem; padding: 0.85rem 1rem; border-radius: 0.5rem; background: #fde8e8; color: #9b1c1c; border: 1px solid #f8b4b4;">
        {{ session('error') }}
    </div>
@endif
