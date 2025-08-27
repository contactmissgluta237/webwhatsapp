@extends('modern.layouts.master')

@section('title', 'Conversation - ' . ($conversation->contact_name ?: $conversation->contact_phone))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">
                <i class="la la-comments text-whatsapp mr-2"></i>
                Conversation avec {{ $conversation->contact_name ?: $conversation->contact_phone }}
            </h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('whatsapp.index') }}">Agents WhatsApp</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.whatsapp.conversations.index', $account->id) }}">Conversations</a></li>
                        <li class="breadcrumb-item active">{{ $conversation->contact_name ?: $conversation->contact_phone }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('customer.whatsapp.conversations.index', $account->id) }}" class="btn btn-outline-whatsapp">
                <i class="la la-arrow-left"></i> {{ __('Retour aux conversations') }}
            </a>
        </div>
    </div>

    <div class="content-body">
        <section id="whatsapp-conversation">
            {{-- Messages de la conversation --}}
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-none border-gray-light">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="la la-comments"></i> 
                                Messages avec {{ $conversation->contact_name ?: $conversation->contact_phone }}
                                <span class="badge badge-whatsapp ml-2">
                                    {{ $conversation->messages->count() }} message{{ $conversation->messages->count() > 1 ? 's' : '' }}
                                </span>
                            </h4>
                        </div>
                        <div class="card-body">
                            @if($conversation->messages->count() > 0)
                                <div class="conversation-messages" style="padding: 15px; 
                                     background: linear-gradient(to bottom, #dddbd1, #d2dbdc); position: relative; min-height: 400px;">
                                    
                                    {{-- Pattern de fond WhatsApp plus simple --}}
                                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
                                         background-color: rgba(255,255,255,0.05);
                                         background-size: 20px 20px;
                                         background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.1) 10px, rgba(255,255,255,0.1) 11px);
                                         pointer-events: none;"></div>
                                    
                                    @foreach($conversation->messages as $message)
                                        @php
                                            $isFromUser = $message->direction === 'inbound'; // Message reçu = utilisateur
                                            $isFromAI = $message->direction === 'outbound' || $message->is_ai_generated; // Message envoyé ou généré par IA
                                        @endphp
                                        
                                        {{-- INVERSION: IA à gauche, Utilisateur à droite --}}
                                        <div class="message-item mb-2 {{ $isFromAI ? 'text-left' : 'text-right' }}" style="position: relative; z-index: 1;">
                                            <div class="message-bubble d-inline-block p-3 shadow-sm" 
                                                 style="max-width: 65%; border-radius: 7.5px; word-wrap: break-word; position: relative;
                                                        {{ $isFromAI 
                                                            ? 'background-color: #dcf8c6; color: #000; border-bottom-left-radius: 2px;' 
                                                            : 'background-color: #ffffff; color: #000; border-bottom-right-radius: 2px;' }}">
                                                
                                                {{-- Petit label pour distinguer IA --}}
                                                @if($message->is_ai_generated)
                                                    <div style="font-size: 10px; color: #25D366; font-weight: bold; margin-bottom: 4px;">
                                                        <i class="la la-robot"></i> Agent IA
                                                    </div>
                                                @endif
                                                
                                                <div class="message-content" style="line-height: 1.4; font-size: 14px;">
                                                    {!! nl2br(e($message->content)) !!}
                                                </div>
                                                
                                                {{-- Product media display --}}
                                                @if($message->message_subtype && $message->message_subtype->value === 'product' && !empty($message->media_urls))
                                                    @php
                                                        $mediaUrls = is_array($message->media_urls) ? $message->media_urls : [];
                                                    @endphp
                                                    
                                                    @if(!empty($mediaUrls))
                                                        <div class="product-media-grid mt-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 4px; max-width: 300px;">
                                                            @foreach($mediaUrls as $mediaUrl)
                                                                @php
                                                                    // Detect media type using helper
                                                                    $mediaType = \App\Services\WhatsApp\MediaTypeDetector::getMediaType($mediaUrl);
                                                                    $extension = strtolower(pathinfo(parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                                                                @endphp
                                                                
                                                                @if($mediaType === 'image')
                                                                    <div class="media-item" style="border-radius: 4px; overflow: hidden; position: relative; background-color: #f5f5f5;">
                                                                        <img src="{{ $mediaUrl }}" 
                                                                             alt="Product media" 
                                                                             style="width: 100%; height: 100px; object-fit: cover; cursor: pointer; display: block;" 
                                                                             onclick="window.open('{{ $mediaUrl }}', '_blank')"
                                                                             loading="lazy"
                                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                                             onload="this.nextElementSibling.style.display='none';">
                                                                        <div style="display: none; width: 100%; height: 100px; align-items: center; justify-content: center; background-color: #f0f0f0; cursor: pointer;" 
                                                                             onclick="window.open('{{ $mediaUrl }}', '_blank')">
                                                                            <div class="text-center">
                                                                                <i class="la la-image" style="font-size: 24px; color: #25D366;"></i>
                                                                                <div style="font-size: 10px; color: #666; margin-top: 2px;">Image</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @elseif($mediaType === 'video')
                                                                    <div class="media-item" style="border-radius: 4px; overflow: hidden; position: relative; background-color: #f5f5f5;">
                                                                        <video style="width: 100%; height: 100px; object-fit: cover; cursor: pointer;" 
                                                                               onclick="window.open('{{ $mediaUrl }}', '_blank')"
                                                                               preload="metadata">
                                                                            <source src="{{ $mediaUrl }}" type="video/{{ $extension }}">
                                                                            <div style="display: flex; width: 100%; height: 100px; align-items: center; justify-content: center; background-color: #f0f0f0; cursor: pointer;" 
                                                                                 onclick="window.open('{{ $mediaUrl }}', '_blank')">
                                                                                <div class="text-center">
                                                                                    <i class="la la-video-camera" style="font-size: 24px; color: #25D366;"></i>
                                                                                    <div style="font-size: 10px; color: #666; margin-top: 2px;">Video</div>
                                                                                </div>
                                                                            </div>
                                                                        </video>
                                                                        {{-- Video play button overlay --}}
                                                                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;">
                                                                            <i class="la la-play-circle" style="font-size: 32px; color: rgba(255,255,255,0.9); text-shadow: 0 0 4px rgba(0,0,0,0.5);"></i>
                                                                        </div>
                                                                    </div>
                                                                @elseif($mediaType === 'audio')
                                                                    <div class="media-item" style="border-radius: 4px; overflow: hidden; position: relative; background-color: #f5f5f5; padding: 10px;">
                                                                        <audio controls style="width: 100%; height: 35px; cursor: pointer;">
                                                                            <source src="{{ $mediaUrl }}" type="audio/{{ $extension }}">
                                                                            Your browser does not support HTML5 audio.
                                                                        </audio>
                                                                        <div class="text-center mt-1">
                                                                            <small style="color: #666; font-size: 10px;">{{ strtoupper($extension) }} Audio</small>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div class="media-item d-flex align-items-center justify-content-center" 
                                                                         style="height: 100px; background-color: #f0f0f0; border-radius: 4px; cursor: pointer;" 
                                                                         onclick="window.open('{{ $mediaUrl }}', '_blank')">
                                                                        <div class="text-center">
                                                                            <i class="la la-file-o" style="font-size: 24px; color: #666;"></i>
                                                                            <div style="font-size: 10px; color: #666; margin-top: 2px;">{{ strtoupper($extension) }}</div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @endif
                                                
                                                <div class="message-footer d-flex align-items-center justify-content-end mt-1" style="font-size: 11px; color: #667781;">
                                                    @php
                                                        $isToday = $message->created_at->isToday();
                                                        $isYesterday = $message->created_at->isYesterday();
                                                        
                                                        if ($isToday) {
                                                            $dateTime = $message->created_at->format('H:i');
                                                        } elseif ($isYesterday) {
                                                            $dateTime = 'Hier ' . $message->created_at->format('H:i');
                                                        } else {
                                                            $dateTime = $message->created_at->format('d/m H:i');
                                                        }
                                                    @endphp
                                                    <span>{{ $dateTime }}</span>
                                                    @if($isFromUser)
                                                        <i class="la la-check ml-1" style="color: #25D366;"></i>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                {{-- Petit script pour scroll automatique vers le bas --}}
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var messagesContainer = document.querySelector('.conversation-messages');
                                        if (messagesContainer) {
                                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                        }
                                    });
                                </script>
                            @else
                                <div class="text-center py-5">
                                    <i class="la la-comments-o text-muted" style="font-size: 4rem;"></i>
                                    <h4 class="text-muted mt-3">Aucun message</h4>
                                    <p class="text-muted">
                                        Cette conversation n'a pas encore de messages.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection