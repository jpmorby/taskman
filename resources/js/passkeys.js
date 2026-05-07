function base64urlToBuffer(base64url) {
    const padded = base64url.replace(/-/g, '+').replace(/_/g, '/')
        + '='.repeat((4 - base64url.length % 4) % 4);
    return Uint8Array.from(atob(padded), c => c.charCodeAt(0)).buffer;
}

function bufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

function parseRegistrationOptions(optionsJson) {
    const options = JSON.parse(optionsJson);
    options.challenge = base64urlToBuffer(options.challenge);
    options.user.id = base64urlToBuffer(options.user.id);
    if (options.excludeCredentials) {
        options.excludeCredentials = options.excludeCredentials.map(c => ({
            ...c,
            id: base64urlToBuffer(c.id),
        }));
    }
    return options;
}

function encodeRegistrationCredential(credential) {
    return {
        id: credential.id,
        rawId: bufferToBase64url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
            attestationObject: bufferToBase64url(credential.response.attestationObject),
        },
        transports: credential.response.getTransports ? credential.response.getTransports() : [],
    };
}

function parseAuthenticationOptions(optionsJson) {
    const options = JSON.parse(optionsJson);
    options.challenge = base64urlToBuffer(options.challenge);
    options.allowCredentials = [];
    return options;
}

function encodeAuthenticationCredential(credential) {
    return {
        id: credential.id,
        rawId: bufferToBase64url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
            authenticatorData: bufferToBase64url(credential.response.authenticatorData),
            signature: bufferToBase64url(credential.response.signature),
            userHandle: credential.response.userHandle
                ? bufferToBase64url(credential.response.userHandle)
                : null,
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('passkeyRegister', () => ({
        error: null,

        async register(optionsJson) {
            this.error = null;
            try {
                const options = parseRegistrationOptions(optionsJson);
                const credential = await navigator.credentials.create({ publicKey: options });
                await this.$wire.confirmPasskey(encodeRegistrationCredential(credential));
            } catch (e) {
                if (e.name === 'InvalidStateError') {
                    this.error = 'This passkey is already registered on this device.';
                } else if (e.name === 'NotAllowedError') {
                    this.error = 'Passkey registration was cancelled.';
                } else {
                    this.error = 'An error occurred during registration. Please try again.';
                }
            }
        },
    }));

    Alpine.data('passkeyAuthenticate', () => ({
        error: null,

        async authenticate(optionsJson) {
            this.error = null;
            try {
                const options = parseAuthenticationOptions(optionsJson);
                const credential = await navigator.credentials.get({ publicKey: options });
                await this.$wire.confirmPasskeyAuth(encodeAuthenticationCredential(credential));
            } catch (e) {
                if (e.name === 'NotAllowedError') {
                    this.error = 'Sign-in was cancelled.';
                } else {
                    this.error = 'An error occurred. Please try again.';
                }
            }
        },
    }));
});
