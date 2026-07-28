<div class="enquiry-form">
    @if($submitted)
        <div class="enquiry-form__success" role="status">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
            <p>Thank you! Our admissions team will contact you shortly.</p>
        </div>
    @else
        <h3 class="enquiry-form__title">{{ $h['enquiry_title'] ?? 'Request Admission' }}</h3>
        @if(!empty($h['enquiry_subtitle']))
            <p class="enquiry-form__subtitle">{{ $h['enquiry_subtitle'] }}</p>
        @endif
        <form wire:submit="submit" class="enquiry-form__fields">
            <div class="form-group">
                <label for="enquiry-name">Your Name</label>
                <input type="text" id="enquiry-name" wire:model="parent_name" class="form-control" placeholder="Parent / Guardian name">
                @error('parent_name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="enquiry-email">Your Email</label>
                <input type="email" id="enquiry-email" wire:model="email" class="form-control" placeholder="your@email.com">
                @error('email')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="enquiry-phone">Your Number</label>
                <input type="tel" id="enquiry-phone" wire:model="phone" class="form-control" placeholder="+250 ...">
                @error('phone')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="enquiry-grade">Class</label>
                <select id="enquiry-grade" wire:model="grade" class="form-control">
                    <option value="">Select Grade</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                </select>
                @error('grade')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn--dark btn--block" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Submit Request for Callback</span>
                <span wire:loading wire:target="submit">Sending...</span>
            </button>
        </form>
    @endif
</div>
