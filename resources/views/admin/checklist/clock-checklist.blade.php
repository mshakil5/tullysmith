@foreach($checklists as $assignment)
@php $existingAnswers = $assignment->answers->keyBy('checklist_item_id'); @endphp

<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        {{ $assignment->checklist->title }}
        <span class="badge bg-info">{{ $assignment->checklist->items->count() }} items</span>
        @if($assignment->answers->count() > 0)
            <span class="badge bg-success">{{ $assignment->answers->count() }} answered</span>
        @endif
    </h6>

    @foreach($assignment->checklist->items as $item)
    @php $existing = $existingAnswers->get($item->id); @endphp

    <div class="mb-3 pb-3 border-bottom">
        <div class="d-flex align-items-start gap-2 mb-2">
            <span class="badge bg-light text-dark mt-1">{{ ucfirst(str_replace('_', ' ', $item->type)) }}</span>
            <p class="mb-0 fw-medium flex-grow-1">
                {{ $item->question }}
                @if($item->is_required)<span class="text-danger ms-1">*</span>@endif
            </p>
        </div>

        @if($existing)
        <small class="text-muted d-block mb-2">
            <i class="ri-user-line me-1"></i>
            Answered by <strong>{{ $existing->answeredBy->name ?? 'You' }}</strong>
            · {{ $existing->updated_at->format('d M Y, h:i A') }}
        </small>
        @endif

        @if($item->type === 'yes_no')
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="answers[{{ $assignment->id }}][{{ $item->id }}]" value="yes" {{ $existing?->answer === 'yes' ? 'checked' : '' }} {{ $item->is_required ? 'required' : '' }}>
                    <label class="form-check-label">Yes</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="answers[{{ $assignment->id }}][{{ $item->id }}]" value="no" {{ $existing?->answer === 'no' ? 'checked' : '' }}>
                    <label class="form-check-label">No</label>
                </div>
            </div>

        @elseif($item->type === 'yes_no_na')
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="answers[{{ $assignment->id }}][{{ $item->id }}]" value="yes" {{ $existing?->answer === 'yes' ? 'checked' : '' }}>
                    <label class="form-check-label">Yes</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="answers[{{ $assignment->id }}][{{ $item->id }}]" value="no" {{ $existing?->answer === 'no' ? 'checked' : '' }}>
                    <label class="form-check-label">No</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="answers[{{ $assignment->id }}][{{ $item->id }}]" value="na" {{ $existing?->answer === 'na' ? 'checked' : '' }}>
                    <label class="form-check-label">N/A</label>
                </div>
            </div>

        @elseif($item->type === 'photo_upload')
            @if($existing?->photo_path)
                <div class="mb-2">
                    <img src="{{ $existing->photo_path }}" class="rounded" style="height:80px;object-fit:cover;">
                </div>
            @endif
            <input type="file" class="form-control" name="photos[{{ $assignment->id }}][{{ $item->id }}]" accept="image/*" {{ ($item->is_required && !$existing?->photo_path) ? 'required' : '' }}>

        @else
            <textarea class="form-control" name="answers[{{ $assignment->id }}][{{ $item->id }}]" rows="2" placeholder="Enter answer..." {{ $item->is_required ? 'required' : '' }}>{{ $existing?->answer }}</textarea>
        @endif
    </div>
    @endforeach
</div>
@endforeach