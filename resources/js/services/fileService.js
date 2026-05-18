
// export async function fetchFiles() {
//     const res = await fetch("/api/files", {
//         headers: { Accept: "application/json" }
//     });
//     return res.json();
// }

// export async function uploadFile(formData) {
//     return fetch("/api/files", {
//         method: "POST",
//         headers: {
//             "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
//             Accept: "application/json"
//         },
//         body: formData
//     });
// }

// export async function downloadFile(id) {
//     return fetch(`/api/files/${id}`);
// }

// export async function deleteFile(id) {
//     return fetch(`/api/files/${id}`, {
//         method: "DELETE",
//         headers: {
//             "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
//         }
//     });
// }