<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Consultas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2"> Consulta RUC y DNI</h1>
                <p class="text-gray-600"> Consultas informacion de SUNAT y RENIEC</p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4"> Consulta RUC</h2>
                <form id="formRuc" class="space-y-4">
                    @csrf
                    <div>
                        <label for="ruc" class="block text-sm font-medium text-gray-700 mb-2">
                            Número de RUC (11 dígitos):
                        </label>
                        <input type="text" id="ruc" 
                            name="ruc" 
                            required
                            maxlength="11"
                            pattern="\d{11}"
                            inputmode="numeric"
                            placeholder="20538856674"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg 
                        focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                        Consultar RUC
                    </button>
                </form>
                <div id="resultadoRuc" class="mt-4 hidden"></div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Consulta DNI</h2>
                <form id="formDni" class="space-y-4">
                    @csrf
                    <div>
                        <label for="dni" class="block text-sm font-medium text-gray-700 mb-2">
                            Número de DNI (8 dígitos)
                        </label>
                        <input 
                            type="text" 
                            id="dni" 
                            name="dni" 
                            required
                            maxlength="8"
                            pattern="\d{8}"
                            inputmode="numeric"
                            placeholder="70238666"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                        >
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200"
                    >
                        Consultar DNI
                    </button>
                </form>
                <div id="resultadoDni" class="mt-4 hidden"></div>
            </div>
        </div>
    </div>

    <script>
        function mostrarResultado(elementId, data, tipo) {
            const container = document.getElementById(elementId);
            container.innerHTML = '';
            container.classList.remove('hidden');

            if (data.success) {
                let html = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-semibold text-green-800 mb-3"> ${tipo} encontrado</h3>
                        <div class="space-y-2">
                `;
                
                for (const [key, value] of Object.entries(data.result)) {
                    if (value && value !== '-') {
                        const label = key.replace(/_/g, ' ');
                        html += `
                            <div class="flex justify-between border-b border-green-100 pb-1">
                                <span class="text-sm text-green-700 capitalize">${label}:</span>
                                <span class="text-sm font-medium text-green-900">${value}</span>
                            </div>
                        `;
                    }
                }
                
                html += `</div></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-700"> Error: ${data.error || 'No se encontraron resultados'}</p>
                    </div>
                `;
            }
        }

        // Form RUC
        document.getElementById('formRuc').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const ruc = formData.get('ruc');

            try {
                const response = await fetch('{{ route("consultas.ruc") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ruc: ruc })
                });

                const data = await response.json();
                mostrarResultado('resultadoRuc', data, 'RUC');
            } catch (error) {
                mostrarResultado('resultadoRuc', { success: false, error: 'Error de conexión' }, 'RUC');
            }
        });

        // Form DNI
        document.getElementById('formDni').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const dni = formData.get('dni');

            try {
                const response = await fetch('{{ route("consultas.dni") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ dni: dni })
                });

                const data = await response.json();
                mostrarResultado('resultadoDni', data, 'DNI');
            } catch (error) {
                mostrarResultado('resultadoDni', { success: false, error: 'Error de conexión' }, 'DNI');
            }
        });

    </script>
</body>

</html>
