interface PuterChatResponse {
    message?: { content?: string };
    text?: string;
    toString(): string;
}

declare global {
    interface Window {
        puter?: {
            ai: {
                chat: (prompt: string, options?: { model?: string }) => Promise<PuterChatResponse | string>;
            };
        };
    }
}

function extractText(response: PuterChatResponse | string): string {
    if (typeof response === 'string') return response.trim();
    if (response.message?.content) return response.message.content.trim();
    if (response.text) return response.text.trim();
    return response.toString().trim();
}

/** Generate teks lewat Puter.js (`puter.ai.chat`), dipanggil langsung dari browser — gratis, tanpa API key. */
export async function generateWithPuter(prompt: string): Promise<string> {
    if (!window.puter) {
        throw new Error('Puter belum termuat. Coba refresh halaman.');
    }

    const response = await window.puter.ai.chat(prompt);
    const text = extractText(response);

    if (!text) {
        throw new Error('Respons Puter kosong.');
    }

    return text;
}
