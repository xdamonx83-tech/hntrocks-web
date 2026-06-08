import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

const root = document.querySelector('[data-prop-lab]');

if (root) {
    const canvas = root.querySelector('[data-prop-canvas]');
    const fallback = root.querySelector('[data-prop-fallback]');
    const status = root.querySelector('[data-prop-status]');
    const currentName = root.querySelector('[data-prop-current-name]');
    const modelSelect = root.querySelector('[data-prop-model]');
    const output = document.querySelector('[data-prop-output]');
    const copyButtons = document.querySelectorAll('[data-prop-copy], [data-prop-copy-inline]');
    const resetButton = root.querySelector('[data-prop-reset]');
    const gridToggle = root.querySelector('[data-prop-grid]');
    const autoFitToggle = root.querySelector('[data-prop-auto-fit]');

    const inputs = {
        x: root.querySelector('[data-prop-x]'),
        y: root.querySelector('[data-prop-y]'),
        z: root.querySelector('[data-prop-z]'),
        rotationY: root.querySelector('[data-prop-rotation-y]'),
        scale: root.querySelector('[data-prop-scale]'),
    };

    let models = [];

    try {
        models = JSON.parse(root.dataset.models || '[]');
    } catch (error) {
        models = [];
    }

    const state = {
        model: null,
        selected: models[0] || null,
        fitScale: 1,
        modelSize: { x: 0, y: 0, z: 0 },
    };

    if (!canvas || models.length === 0) {
        if (fallback) {
            fallback.hidden = false;
        }
    } else {
        boot();
    }

    function boot() {
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0x11110f);
        scene.fog = new THREE.Fog(0x11110f, 8, 18);

        const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: false });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.25;

        const camera = new THREE.PerspectiveCamera(42, 1, 0.01, 100);
        camera.position.set(3.2, 2.3, 4.6);

        const controls = new OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.08;
        controls.target.set(0, 0.9, 0);
        controls.minDistance = 1.2;
        controls.maxDistance = 9;
        controls.update();

        const group = new THREE.Group();
        scene.add(group);

        const grid = new THREE.GridHelper(8, 16, 0xd6a84f, 0x343025);
        grid.position.y = 0;
        grid.material.transparent = true;
        grid.material.opacity = 0.28;
        scene.add(grid);

        const floor = new THREE.Mesh(
            new THREE.PlaneGeometry(8, 8),
            new THREE.MeshStandardMaterial({ color: 0x1a1a18, roughness: 0.88, metalness: 0.02 })
        );
        floor.rotation.x = -Math.PI / 2;
        floor.position.y = -0.005;
        scene.add(floor);

        const ambient = new THREE.HemisphereLight(0xffecd0, 0x263219, 1.6);
        scene.add(ambient);

        const key = new THREE.DirectionalLight(0xffd58a, 2.4);
        key.position.set(3.5, 4.8, 3.2);
        scene.add(key);

        const fill = new THREE.PointLight(0xd6a84f, 6, 9, 1.8);
        fill.position.set(-2.4, 2.2, 2.8);
        scene.add(fill);

        const loader = new GLTFLoader();

        function resize() {
            const rect = canvas.parentElement.getBoundingClientRect();
            const width = Math.max(320, rect.width);
            const height = Math.max(300, rect.height);
            renderer.setSize(width, height, false);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
        }

        function setStatus(message, icon = 'ph-spinner-gap') {
            if (!status) {
                return;
            }

            status.innerHTML = `<i class="ph ${icon}" aria-hidden="true"></i>${message}`;
        }

        function selectedModel() {
            const value = modelSelect?.value || state.selected?.file || '';
            return models.find((item) => item.file === value) || models[0] || null;
        }

        function clearModel() {
            while (group.children.length > 0) {
                const child = group.children.pop();
                child.traverse?.((node) => {
                    if (node.geometry) node.geometry.dispose?.();
                });
            }
            state.model = null;
        }

        function loadCurrentModel() {
            const model = selectedModel();

            if (!model) {
                setStatus(root.dataset.emptyLabel || 'No model', 'ph-warning-circle');
                return;
            }

            state.selected = model;
            clearModel();
            setStatus(root.dataset.loadingLabel || 'Loading model', 'ph-spinner-gap');

            loader.load(
                model.url,
                (gltf) => {
                    state.model = gltf.scene;
                    normalizeModel(gltf.scene);
                    group.add(gltf.scene);
                    applyTransform();
                    updateOutput();
                    setStatus(model.size_label || 'Geladen', 'ph-check-circle');

                    if (currentName) {
                        currentName.textContent = model.label;
                    }
                },
                undefined,
                () => {
                    setStatus(root.dataset.loadError || 'Model could not be loaded', 'ph-warning-circle');
                }
            );
        }

        function normalizeModel(model) {
            model.traverse((node) => {
                if (!node.isMesh) {
                    return;
                }

                node.castShadow = false;
                node.receiveShadow = true;

                if (node.material) {
                    const materials = Array.isArray(node.material) ? node.material : [node.material];
                    materials.forEach((material) => {
                        material.side = THREE.FrontSide;
                        material.needsUpdate = true;
                    });
                }
            });

            const box = new THREE.Box3().setFromObject(model);
            const size = new THREE.Vector3();
            const center = new THREE.Vector3();
            box.getSize(size);
            box.getCenter(center);

            model.position.sub(center);

            const maxAxis = Math.max(size.x, size.y, size.z, 0.001);
            state.fitScale = autoFitToggle?.checked ? 1.8 / maxAxis : 1;
            state.modelSize = { x: size.x, y: size.y, z: size.z };
        }

        function numeric(input, fallback = 0) {
            const value = Number.parseFloat(input?.value || '');
            return Number.isFinite(value) ? value : fallback;
        }

        function applyTransform() {
            if (!state.model) {
                return;
            }

            const userScale = Math.max(0.01, numeric(inputs.scale, 1));
            const finalScale = state.fitScale * userScale;

            group.position.set(numeric(inputs.x), numeric(inputs.y), numeric(inputs.z));
            group.rotation.set(0, THREE.MathUtils.degToRad(numeric(inputs.rotationY)), 0);
            group.scale.setScalar(finalScale);
        }

        function outputData() {
            const model = selectedModel();
            const userScale = Math.max(0.01, numeric(inputs.scale, 1));
            const finalScale = Number((state.fitScale * userScale).toFixed(5));

            return {
                model: model?.file || null,
                label: model?.label || null,
                position: {
                    x: Number(numeric(inputs.x).toFixed(2)),
                    y: Number(numeric(inputs.y).toFixed(2)),
                    z: Number(numeric(inputs.z).toFixed(2)),
                },
                rotation: {
                    x: 0,
                    y: Number(THREE.MathUtils.degToRad(numeric(inputs.rotationY)).toFixed(5)),
                    z: 0,
                },
                scale: finalScale,
                scaleMultiplier: Number(userScale.toFixed(2)),
                fitScale: Number(state.fitScale.toFixed(5)),
            };
        }

        function updateOutput() {
            applyTransform();

            if (gridToggle) {
                grid.visible = gridToggle.checked;
            }

            if (output) {
                output.value = JSON.stringify(outputData(), null, 2);
            }
        }

        function resetControls() {
            inputs.x.value = '0';
            inputs.y.value = '0';
            inputs.z.value = '0';
            inputs.rotationY.value = '0';
            inputs.scale.value = '1';
            loadCurrentModel();
        }

        async function copyOutput(button) {
            const text = output?.value || JSON.stringify(outputData(), null, 2);

            try {
                await navigator.clipboard.writeText(text);
                const old = button.innerHTML;
                button.innerHTML = `<i class="ph ph-check" aria-hidden="true"></i>${root.dataset.copiedLabel || 'Copied'}`;
                setTimeout(() => {
                    button.innerHTML = old;
                }, 1400);
            } catch (error) {
                if (output) {
                    output.focus();
                    output.select();
                }
            }
        }

        Object.values(inputs).forEach((input) => {
            input?.addEventListener('input', updateOutput);
            input?.addEventListener('change', updateOutput);
        });

        modelSelect?.addEventListener('change', loadCurrentModel);
        gridToggle?.addEventListener('change', updateOutput);
        autoFitToggle?.addEventListener('change', loadCurrentModel);
        resetButton?.addEventListener('click', resetControls);
        copyButtons.forEach((button) => button.addEventListener('click', () => copyOutput(button)));

        window.addEventListener('resize', resize);

        resize();
        loadCurrentModel();

        renderer.setAnimationLoop(() => {
            controls.update();
            renderer.render(scene, camera);
        });
    }
}
