@foreach($checklists as $index => $assignment)
@php $existingAnswers = $assignment->answers->keyBy('checklist_item_id'); @endphp

<div class="accordion mb-3" id="ca{{ $index }}">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cc{{ $index }}">
                <strong>{{ $assignment->checklist->title }}</strong>
                <span class="badge bg-info ms-2">{{ $assignment->checklist->items->count() }} items</span>
                @if($assignment->answers->count() > 0)
                    <span class="badge bg-success ms-2">{{ $assignment->answers->count() }} answered</span>
                @endif
            </button>
        </h2>
        <div id="cc{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}">
            <div class="accordion-body">
                <form class="checklist-answer-form" data-id="{{ $assignment->id }}" enctype="multipart/form-data">
                    @csrf
                    @foreach($assignment->checklist->items as $item)
                    @php $existing = $existingAnswers->get($item->id); @endphp

                    <div class="mb-4 pb-3 border-bottom">
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
                            Answered by <strong>{{ $existing->answeredBy->name ?? 'Unknown' }}</strong>
                            · {{ $existing->updated_at->format('d M Y, h:i A') }}
                        </small>
                        @endif

                        @if($item->type === 'yes_no')
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answers[{{ $item->id }}]" value="yes" {{ $existing?->answer === 'yes' ? 'checked' : '' }}>
                                    <label class="form-check-label">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answers[{{ $item->id }}]" value="no" {{ $existing?->answer === 'no' ? 'checked' : '' }}>
                                    <label class="form-check-label">No</label>
                                </div>
                            </div>

                        @elseif($item->type === 'yes_no_na')
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answers[{{ $item->id }}]" value="yes" {{ $existing?->answer === 'yes' ? 'checked' : '' }}>
                                    <label class="form-check-label">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answers[{{ $item->id }}]" value="no" {{ $existing?->answer === 'no' ? 'checked' : '' }}>
                                    <label class="form-check-label">No</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answers[{{ $item->id }}]" value="na" {{ $existing?->answer === 'na' ? 'checked' : '' }}>
                                    <label class="form-check-label">N/A</label>
                                </div>
                            </div>

                        @elseif($item->type === 'photo_upload')
                            @if($existing?->photo_path)
                                <div class="mb-2">
                                    <img src="{{ $existing->photo_path }}" class="rounded"
                                         style="height:80px;object-fit:cover;cursor:pointer;"
                                         data-bs-toggle="modal" data-bs-target="#imgModal"
                                         data-src="{{ $existing->photo_path }}">
                                </div>
                            @endif
                            <input type="file" class="form-control" name="photos[{{ $item->id }}]" accept="image/*">

                        @else
                            {{-- text_input --}}
                            <textarea class="form-control" name="answers[{{ $item->id }}]" rows="2" placeholder="Enter answer...">{{ $existing?->answer }}</textarea>
                        @endif
                    </div>
                    @endforeach

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">{{ $assignment->answers->count() }} / {{ $assignment->checklist->items->count() }} answered</small>
                        <div class="d-flex gap-2">
                            @hasanyrole('Super Admin|Admin')
                            <button type="button" class="btn btn-sm btn-soft-danger deleteChecklistBtn" data-id="{{ $assignment->id }}">
                                <i class="ri-delete-bin-line me-1"></i> Remove
                            </button>
                            @endhasanyrole
                            <button type="submit" class="btn btn-sm btn-primary save-answers-btn">
                                <i class="ri-save-line me-1"></i> Save Answers
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach