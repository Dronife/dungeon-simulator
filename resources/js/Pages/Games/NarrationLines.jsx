// --- Avatar ---

function Avatar({ characterId, show }) {
    if (!characterId) return null;

    const parts = characterId.split('_');
    const letter = (parts[parts.length - 1] || '?')[0].toUpperCase();

    if (!show) {
        return <div className="w-8 shrink-0" />;
    }

    return (
        <div className="w-8 h-8 shrink-0 rounded-md bg-[#1e1f25] border border-[#554434]/50 flex items-center justify-center mt-1">
            <span className="text-[#a38d7a] font-sans text-xs font-bold">{letter}</span>
        </div>
    );
}

// --- Line components ---

function NarratorLine({ text }) {
    return <p className="text-[#e3e1e9]/90 font-narration text-[1.05rem] leading-relaxed">{text}</p>;
}

function DialogueLine({ speaker, direction, text, characterId, showAvatar }) {
    return (
        <div className="flex gap-4">
            <Avatar characterId={characterId} show={showAvatar} />
            <div className="min-w-0 flex-1">
                {speaker && showAvatar && (
                    <p className="text-[#efc84e]/90 font-sans text-xs font-bold uppercase tracking-wide mb-1">
                        {speaker}
                    </p>
                )}
                {direction && (
                    <p className="text-[#a38d7a] font-sans text-xs italic mb-1">{direction}</p>
                )}
                <p className="text-[#e3e1e9] font-narration text-[1.05rem] leading-relaxed">"{text}"</p>
            </div>
        </div>
    );
}

function ActionLine({ speaker, text, characterId, showAvatar }) {
    return (
        <div className="flex gap-4">
            <Avatar characterId={characterId} show={showAvatar} />
            <div className="min-w-0">
                {showAvatar && (
                    <p className="text-[#a38d7a] font-sans text-xs font-bold uppercase tracking-wide mb-1">
                        {speaker}
                    </p>
                )}
                <p className="text-[#dbc2ad] font-narration italic text-[1.05rem] leading-relaxed">{text}</p>
            </div>
        </div>
    );
}

function WhisperLine({ text, characterId, showAvatar }) {
    return (
        <div className="flex gap-4">
            <Avatar characterId={characterId} show={showAvatar} />
            <div className="min-w-0 border-l-2 border-[#554434] pl-3">
                <p className="text-[#a38d7a] font-narration italic text-[1.05rem] leading-relaxed">"{text}"</p>
            </div>
        </div>
    );
}

function MechanicLine({ text }) {
    const isSuccess = /success/i.test(text);
    const isFailure = /fail/i.test(text);

    const colorClass = isSuccess ? 'text-emerald-500/60' : isFailure ? 'text-red-500/60' : 'text-[#a38d7a]/60';

    return (
        <div className="flex justify-center py-4">
            <p className={`${colorClass} font-mono text-xs tracking-widest uppercase font-semibold`}>
                [ {text} ]
            </p>
        </div>
    );
}

function HeadingLine({ text }) {
    return (
        <div className="mt-10 mb-6 border-b border-[#554434]/50 pb-3 flex justify-center">
            <p className="text-[#a38d7a] font-sans font-semibold text-xs uppercase tracking-[0.2em] text-center">
                {text}
            </p>
        </div>
    );
}

function ItalicLine({ text }) {
    return <p className="text-[#a38d7a] font-narration italic text-[1.05rem] leading-relaxed">{text}</p>;
}

// --- Rendering ---

function renderLine(line, showAvatar) {
    switch (line.type) {
        case 'narrator':
            return <NarratorLine text={line.text} />;
        case 'dialogue':
            return <DialogueLine speaker={line.speaker} direction={line.direction} text={line.text} characterId={line.character_id} showAvatar={showAvatar} />;
        case 'action':
            return <ActionLine speaker={line.speaker} text={line.text} characterId={line.character_id} showAvatar={showAvatar} />;
        case 'whisper':
            return <WhisperLine text={line.text} characterId={line.character_id} showAvatar={showAvatar} />;
        case 'mechanic':
            return <MechanicLine text={line.text} />;
        case 'heading':
            return <HeadingLine text={line.text} />;
        case 'italic':
            return <ItalicLine text={line.text} />;
        default:
            return <p className="text-[#dbc2ad] font-narration">{line.text}</p>;
    }
}

// --- Message containers ---

export function PlayerMessage({ text }) {
    return (
        <div className="py-3 border-y border-[#efc84e]/10 bg-[#efc84e]/5 px-6 -mx-6 md:-mx-10">
            <p className="text-[#efc84e]/80 font-sans text-sm">
                <span className="text-[#a38d7a] text-[10px] uppercase tracking-wider font-bold mr-2">Action:</span>
                {text}
            </p>
        </div>
    );
}

export function LlmMessage({ content }) {
    let lines = [];
    try {
        lines = typeof content === 'string' ? JSON.parse(content) : content;
    } catch {
        return <p className="text-[#e3e1e9]/90 font-narration">{content}</p>;
    }

    if (!Array.isArray(lines)) {
        return <p className="text-[#e3e1e9]/90 font-narration">{String(content)}</p>;
    }

    let lastCharacterId = null;

    return (
        <div>
            {lines.map((line, i) => {
                const prev = i > 0 ? lines[i - 1]?.type : null;
                const isHeading = line.type === 'heading';
                const isMechanic = line.type === 'mechanic';
                const typeChanged = prev && prev !== line.type;

                let spacing = 'mt-2';
                if (i === 0) spacing = '';
                else if (isHeading) spacing = '';
                else if (prev === 'heading') spacing = 'mt-4';
                else if (isMechanic || prev === 'mechanic') spacing = 'mt-4';
                else if (typeChanged || line.character_id !== lastCharacterId) spacing = 'mt-6';

                let showAvatar = false;
                if (line.character_id) {
                    showAvatar = line.character_id !== lastCharacterId;
                    lastCharacterId = line.character_id;
                } else {
                    lastCharacterId = null;
                }

                return (
                    <div key={i} className={spacing}>
                        {renderLine(line, showAvatar)}
                    </div>
                );
            })}
        </div>
    );
}

export function LoadingIndicator() {
    return (
        <div className="flex items-center gap-1.5 py-2">
            <div className="w-1.5 h-1.5 bg-[#efc84e]/40 rounded-full animate-bounce [animation-delay:0ms]" />
            <div className="w-1.5 h-1.5 bg-[#efc84e]/40 rounded-full animate-bounce [animation-delay:150ms]" />
            <div className="w-1.5 h-1.5 bg-[#efc84e]/40 rounded-full animate-bounce [animation-delay:300ms]" />
        </div>
    );
}
