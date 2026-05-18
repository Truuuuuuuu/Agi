
// export async function generateKey() {
//     return crypto.subtle.generateKey(
//         { name: "AES-GCM", length: 256 },
//         true,
//         ["encrypt", "decrypt"]
//     );
// }

// export async function importKey(jwk) {
//     return crypto.subtle.importKey(
//         "jwk",
//         jwk,
//         { name: "AES-GCM", length: 256 },
//         true,
//         ["encrypt", "decrypt"]
//     );
// }

// export async function exportKey(key) {
//     return crypto.subtle.exportKey("jwk", key);
// }

// export async function sha256(buffer) {
//     const hash = await crypto.subtle.digest("SHA-256", buffer);
//     return [...new Uint8Array(hash)]
//         .map(b => b.toString(16).padStart(2, "0"))
//         .join("");
// }

// export async function encrypt(key, iv, data) {
//     return crypto.subtle.encrypt(
//         { name: "AES-GCM", iv },
//         key,
//         data
//     );
// }

// export async function decrypt(key, iv, data) {
//     return crypto.subtle.decrypt(
//         { name: "AES-GCM", iv },
//         key,
//         data
//     );
// }