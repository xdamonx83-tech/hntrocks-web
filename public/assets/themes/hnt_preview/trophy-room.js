import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

const roots = document.querySelectorAll('[data-trophy-room]');

const canUseWebGL = () => {
    try {
        const canvas = document.createElement('canvas');
        return Boolean(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
    } catch (error) {
        return false;
    }
};

const setText = (selector, value) => {
    document.querySelectorAll(selector).forEach((node) => {
        node.textContent = value || '';
    });
};

const parseTrophies = (root) => {
    try {
        const trophies = JSON.parse(root.dataset.trophies || '[]');
        return Array.isArray(trophies) ? trophies : [];
    } catch (error) {
        return [];
    }
};

const createCup = (accent) => {
    const group = new THREE.Group();
    const metal = new THREE.MeshStandardMaterial({
        color: new THREE.Color(accent || '#D6A84F'),
        metalness: 0.72,
        roughness: 0.24,
    });
    const dark = new THREE.MeshStandardMaterial({ color: '#211b14', metalness: 0.25, roughness: 0.55 });

    const bowl = new THREE.Mesh(new THREE.CylinderGeometry(0.28, 0.18, 0.28, 36, 1, false), metal);
    bowl.position.y = 0.42;
    group.add(bowl);

    const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.075, 0.32, 24), metal);
    stem.position.y = 0.13;
    group.add(stem);

    const base = new THREE.Mesh(new THREE.CylinderGeometry(0.25, 0.30, 0.12, 32), dark);
    base.position.y = -0.11;
    group.add(base);

    const handleLeft = new THREE.Mesh(new THREE.TorusGeometry(0.17, 0.018, 10, 32, Math.PI), metal);
    handleLeft.rotation.z = Math.PI / 2;
    handleLeft.position.set(-0.28, 0.42, 0);
    group.add(handleLeft);

    const handleRight = handleLeft.clone();
    handleRight.position.x = 0.28;
    handleRight.rotation.z = -Math.PI / 2;
    group.add(handleRight);

    return group;
};

const createMedal = (accent) => {
    const group = new THREE.Group();
    const material = new THREE.MeshStandardMaterial({
        color: new THREE.Color(accent || '#9BE447'),
        metalness: 0.52,
        roughness: 0.30,
    });
    const ribbonMaterial = new THREE.MeshStandardMaterial({ color: '#2b241b', metalness: 0.08, roughness: 0.70 });

    const medal = new THREE.Mesh(new THREE.CylinderGeometry(0.28, 0.28, 0.065, 48), material);
    medal.rotation.x = Math.PI / 2;
    medal.position.y = 0.24;
    group.add(medal);

    const ring = new THREE.Mesh(new THREE.TorusGeometry(0.31, 0.014, 12, 48), material);
    ring.position.y = 0.24;
    group.add(ring);

    const ribbon = new THREE.Mesh(new THREE.ConeGeometry(0.22, 0.48, 4), ribbonMaterial);
    ribbon.rotation.z = Math.PI / 4;
    ribbon.position.y = 0.62;
    group.add(ribbon);

    return group;
};

const createCrystal = (accent) => {
    const group = new THREE.Group();
    const material = new THREE.MeshStandardMaterial({
        color: new THREE.Color(accent || '#7FB069'),
        metalness: 0.18,
        roughness: 0.12,
        transparent: true,
        opacity: 0.78,
        emissive: new THREE.Color(accent || '#7FB069'),
        emissiveIntensity: 0.08,
    });
    const baseMaterial = new THREE.MeshStandardMaterial({ color: '#161613', metalness: 0.20, roughness: 0.56 });

    const crystal = new THREE.Mesh(new THREE.ConeGeometry(0.22, 0.82, 5), material);
    crystal.position.y = 0.34;
    crystal.rotation.y = Math.PI / 5;
    group.add(crystal);

    const base = new THREE.Mesh(new THREE.CylinderGeometry(0.28, 0.32, 0.12, 5), baseMaterial);
    base.position.y = -0.12;
    group.add(base);

    return group;
};

const createBadge = (accent) => {
    const group = new THREE.Group();
    const material = new THREE.MeshStandardMaterial({
        color: new THREE.Color(accent || '#CFA149'),
        metalness: 0.45,
        roughness: 0.34,
    });
    const back = new THREE.MeshStandardMaterial({ color: '#1f1d19', metalness: 0.15, roughness: 0.55 });

    const shield = new THREE.Mesh(new THREE.CylinderGeometry(0.28, 0.23, 0.08, 6), material);
    shield.rotation.x = Math.PI / 2;
    shield.position.y = 0.24;
    group.add(shield);

    const inner = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.16, 0.085, 6), back);
    inner.rotation.x = Math.PI / 2;
    inner.position.set(0, 0.24, 0.006);
    group.add(inner);

    const base = new THREE.Mesh(new THREE.CylinderGeometry(0.24, 0.28, 0.10, 28), back);
    base.position.y = -0.15;
    group.add(base);

    return group;
};

const createLocked = () => {
    const group = new THREE.Group();
    const material = new THREE.MeshStandardMaterial({ color: '#5B5A50', metalness: 0.16, roughness: 0.70 });
    const dark = new THREE.MeshStandardMaterial({ color: '#151513', metalness: 0.10, roughness: 0.80 });

    const box = new THREE.Mesh(new THREE.BoxGeometry(0.44, 0.42, 0.16), material);
    box.position.y = 0.10;
    group.add(box);

    const shackle = new THREE.Mesh(new THREE.TorusGeometry(0.18, 0.025, 12, 36, Math.PI), material);
    shackle.rotation.z = Math.PI;
    shackle.position.y = 0.40;
    group.add(shackle);

    const keyhole = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 0.022, 18), dark);
    keyhole.rotation.x = Math.PI / 2;
    keyhole.position.set(0, 0.12, 0.091);
    group.add(keyhole);

    return group;
};


const createLabelTexture = (title, accent) => {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    const width = 512;
    const height = 128;
    canvas.width = width;
    canvas.height = height;

    if (!context) {
        return null;
    }

    context.clearRect(0, 0, width, height);
    context.fillStyle = 'rgba(20, 20, 18, 0.76)';
    context.strokeStyle = accent || '#D6A84F';
    context.lineWidth = 3;
    context.beginPath();
    if (typeof context.roundRect === 'function') {
        context.roundRect(18, 22, width - 36, height - 44, 22);
    } else {
        context.rect(18, 22, width - 36, height - 44);
    }
    context.fill();
    context.stroke();

    context.font = '800 34px Inter, Arial, sans-serif';
    context.fillStyle = '#F2E8D8';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    const label = String(title || '').slice(0, 28);
    context.fillText(label, width / 2, height / 2 + 1);

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    return texture;
};

const createNameplate = (trophy) => {
    const texture = createLabelTexture(trophy.title || '', trophy.accent || '#D6A84F');

    if (!texture) {
        return null;
    }

    const material = new THREE.SpriteMaterial({
        map: texture,
        transparent: true,
        opacity: 0.92,
        depthWrite: false,
    });
    const sprite = new THREE.Sprite(material);
    sprite.scale.set(0.86, 0.22, 1);
    sprite.userData.isLabel = true;
    return sprite;
};

const createSpotRing = (accent) => {
    const material = new THREE.MeshBasicMaterial({
        color: new THREE.Color(accent || '#D6A84F'),
        transparent: true,
        opacity: 0.12,
        side: THREE.DoubleSide,
        depthWrite: false,
    });
    const ring = new THREE.Mesh(new THREE.RingGeometry(0.38, 0.50, 48), material);
    ring.rotation.x = -Math.PI / 2;
    ring.userData.baseOpacity = 0.12;
    return ring;
};

const createWallPlaque = () => {
    // The old procedural hall guide plaques (Rare & Epic / Legendary) looked
    // like floating UI signs inside the custom Godot room. They are disabled
    // now; the final trophy-room marker should be placed in the Godot scene
    // itself when needed, so it matches the room layout precisely.
    return null;
};

const createTrophyMesh = (trophy) => {
    switch (trophy.type) {
        case 'cup':
            return createCup(trophy.accent);
        case 'medal':
            return createMedal(trophy.accent);
        case 'crystal':
            return createCrystal(trophy.accent);
        case 'badge':
            return createBadge(trophy.accent);
        case 'locked':
            return createLocked();
        default:
            return createCup(trophy.accent);
    }
};


const createNormalizedModelClone = (source, desiredHeight = 1) => {
    if (!source) {
        return null;
    }

    const model = source.clone(true);
    model.traverse((child) => {
        if (child.isMesh) {
            child.castShadow = true;
            child.receiveShadow = true;
            if (child.material) {
                child.material = child.material.clone ? child.material.clone() : child.material;
                child.material.needsUpdate = true;
            }
        }
    });

    const sourceBox = new THREE.Box3().setFromObject(model);
    const sourceSize = new THREE.Vector3();
    sourceBox.getSize(sourceSize);

    if (!Number.isFinite(sourceSize.y) || sourceSize.y <= 0.0001) {
        return model;
    }

    const scale = desiredHeight / sourceSize.y;
    model.scale.multiplyScalar(scale);

    const box = new THREE.Box3().setFromObject(model);
    const center = new THREE.Vector3();
    box.getCenter(center);
    model.position.x -= center.x;
    model.position.z -= center.z;
    model.position.y -= box.min.y;

    return model;
};


const hallConfigs = [
    { key: 'starter', title: 'Unlocked Hall', centerZ: -2.15, displayZ: -4.38, accent: '#9BE447' },
    { key: 'rare', title: 'Rare & Epic Hall', centerZ: -8.25, displayZ: -10.48, accent: '#7FB069' },
    { key: 'legendary', title: 'Legendary Hall', centerZ: -14.35, displayZ: -16.58, accent: '#D6A84F' },
];

const trophyHallKey = (trophy) => {
    const haystack = `${trophy?.rarity || ''} ${trophy?.type || ''} ${trophy?.title || ''}`.toLowerCase();

    if (haystack.includes('gold') || haystack.includes('legendary') || haystack.includes('winner') || haystack.includes('gewinner')) {
        return 'legendary';
    }

    if (haystack.includes('epic') || haystack.includes('rare') || haystack.includes('selten') || haystack.includes('crystal')) {
        return 'rare';
    }

    return 'starter';
};

const layoutTrophiesByHall = (trophies) => {
    const slotXs = [-3.72, -2.72, -1.92, 1.92, 2.72, 3.72];
    const counters = Object.fromEntries(hallConfigs.map((hall) => [hall.key, 0]));

    return trophies.map((trophy) => {
        const hallKey = trophyHallKey(trophy);
        const hall = hallConfigs.find((entry) => entry.key === hallKey) || hallConfigs[0];
        const slot = counters[hall.key] || 0;
        counters[hall.key] = slot + 1;
        const row = Math.floor(slot / slotXs.length);
        const x = slotXs[slot % slotXs.length];
        const z = hall.displayZ + 0.46 - (row * 0.52);

        return {
            ...trophy,
            hall: hall.key,
            hallTitle: hall.title,
            position: [x, 1.04, z],
        };
    });
};

const initRoom = (root) => {
    const canvas = root.querySelector('[data-trophy-canvas]');
    const fallback = root.querySelector('[data-trophy-fallback]');
    const status = root.querySelector('[data-trophy-status]');
    const resetButton = root.querySelector('[data-trophy-reset]');
    const fullscreenButton = root.querySelector('[data-trophy-fullscreen]');
    const loadingScreen = root.querySelector('[data-trophy-loading-screen]');
    const loadingBar = root.querySelector('[data-trophy-loading-bar]');
    const loadingProgress = root.querySelector('[data-trophy-loading-progress]');
    const loadingText = root.querySelector('[data-trophy-loading-text]');
    const enterButton = root.querySelector('[data-trophy-enter]');
    const lockPrompt = root.querySelector('[data-trophy-lock-prompt]');
    const touchControls = root.querySelector('[data-trophy-touch-controls]');
    const touchJoystick = root.querySelector('[data-trophy-touch-joystick]');
    const touchJoystickKnob = touchJoystick?.querySelector('span') || null;
    const touchLook = root.querySelector('[data-trophy-touch-look]');
    const touchInteract = root.querySelector('[data-trophy-touch-interact]');
    const ingameCard = root.querySelector('[data-trophy-ingame-card]');
    const ingameTitle = root.querySelector('[data-trophy-ingame-title]');
    const ingameMeta = root.querySelector('[data-trophy-ingame-meta]');
    const trophies = layoutTrophiesByHall(parseTrophies(root));
    let isRoomLoading = Boolean(root.dataset.modelCustomRoom);

    const setRoomLoading = (percent = 0, label = null) => {
        if (!loadingScreen) {
            return;
        }

        const safePercent = THREE.MathUtils.clamp(Number.isFinite(percent) ? percent : 0, 0, 100);
        root.classList.add('is-loading-room');
        loadingScreen.hidden = false;

        if (loadingBar) {
            loadingBar.style.width = `${safePercent}%`;
        }
        if (loadingProgress) {
            loadingProgress.textContent = `${Math.round(safePercent)}%`;
        }
        if (loadingText && label) {
            loadingText.textContent = label;
        }
        if (status) {
            status.innerHTML = '<i class="ph ph-spinner-gap" aria-hidden="true"></i>' + (root.dataset.loadingLabel || 'Raum wird geladen');
        }
    };

    const finishRoomLoading = () => {
        isRoomLoading = false;
        setRoomLoading(100, root.dataset.loadingReadyLabel || root.dataset.readyEmpty || 'Bereit');
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                root.classList.remove('is-loading-room');
                if (loadingScreen) {
                    loadingScreen.hidden = true;
                }
                updateLockUi();
            });
        });
    };

    if (isRoomLoading) {
        setRoomLoading(0, root.dataset.loadingProgressLabel || root.dataset.loadingLabel || 'Raum wird geladen');
    }

    if (!canvas || !canUseWebGL()) {
        if (loadingScreen) {
            loadingScreen.hidden = true;
        }
        root.classList.remove('is-loading-room');
        if (fallback) {
            fallback.hidden = false;
        }
        if (status) {
            status.textContent = root.dataset.webglError || 'WebGL is not available.';
        }
        return;
    }

    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
    const pixelRatioCap = window.matchMedia?.('(pointer: coarse)').matches ? 1.5 : 2;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, pixelRatioCap));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.38;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#171613');
    scene.fog = new THREE.Fog('#171613', 10.5, 27.5);

    const dividerWalls = [
        { z: -5.55 },
        { z: -11.65 },
    ];
    const roomBounds = {
        minX: -4.58,
        maxX: 4.58,
        minZ: -17.56,
        maxZ: 1.66,
        playerY: 1.68,
        doorHalfWidth: 1.72,
        wallHalfDepth: 0.22,
        playerRadius: 0.28,
    };
    const displayDoorWalls = hallConfigs
        .filter((hall) => hall.key !== 'legendary')
        .map((hall) => ({ z: hall.displayZ }));
    const backDisplayWalls = hallConfigs
        .filter((hall) => hall.key === 'legendary')
        .map((hall) => ({ z: hall.displayZ }));
    const collisionBoxes = [
        ...dividerWalls.flatMap((wall) => ([
            { minX: roomBounds.minX - 0.80, maxX: -roomBounds.doorHalfWidth, minZ: wall.z - roomBounds.wallHalfDepth, maxZ: wall.z + roomBounds.wallHalfDepth },
            { minX: roomBounds.doorHalfWidth, maxX: roomBounds.maxX + 0.80, minZ: wall.z - roomBounds.wallHalfDepth, maxZ: wall.z + roomBounds.wallHalfDepth },
        ])),
        ...displayDoorWalls.flatMap((wall) => ([
            { minX: roomBounds.minX - 0.80, maxX: -roomBounds.doorHalfWidth, minZ: wall.z - roomBounds.wallHalfDepth, maxZ: wall.z + roomBounds.wallHalfDepth },
            { minX: roomBounds.doorHalfWidth, maxX: roomBounds.maxX + 0.80, minZ: wall.z - roomBounds.wallHalfDepth, maxZ: wall.z + roomBounds.wallHalfDepth },
        ])),
        ...backDisplayWalls.map((wall) => ({
            minX: roomBounds.minX - 0.80,
            maxX: roomBounds.maxX + 0.80,
            minZ: wall.z - roomBounds.wallHalfDepth,
            maxZ: wall.z + roomBounds.wallHalfDepth,
        })),
    ];
    const startPosition = new THREE.Vector3(0, roomBounds.playerY, 1.24);
    const startYaw = 0;
    const startPitch = -0.06;

    const camera = new THREE.PerspectiveCamera(66, 1, 0.1, 90);
    camera.rotation.order = 'YXZ';
    camera.position.copy(startPosition);

    let yaw = startYaw;
    let pitch = startPitch;
    let isPointerLocked = false;
    const mouseSensitivity = 0.00215;
    const baseSpeed = 2.05;
    const runSpeed = 3.55;
    const keys = new Set();
    const prefersTouchControls = Boolean(touchControls)
        && (window.matchMedia?.('(pointer: coarse)').matches || 'ontouchstart' in window || navigator.maxTouchPoints > 0);
    const touchState = {
        moveX: 0,
        moveY: 0,
        joystickPointer: null,
        lookPointer: null,
        lookLastX: 0,
        lookLastY: 0,
    };

    if (prefersTouchControls) {
        root.classList.add('has-touch-controls');
        touchControls?.removeAttribute('aria-hidden');
    }

    const applyCameraRotation = () => {
        pitch = THREE.MathUtils.clamp(pitch, -0.72, 0.46);
        camera.rotation.x = pitch;
        camera.rotation.y = yaw;
        camera.rotation.z = 0;
    };

    const wouldCollideWithBox = (x, z, box) => (
        x > box.minX - roomBounds.playerRadius
        && x < box.maxX + roomBounds.playerRadius
        && z > box.minZ - roomBounds.playerRadius
        && z < box.maxZ + roomBounds.playerRadius
    );

    const isWalkable = (x, z) => {
        if (x < roomBounds.minX + roomBounds.playerRadius || x > roomBounds.maxX - roomBounds.playerRadius) {
            return false;
        }
        if (z < roomBounds.minZ + roomBounds.playerRadius || z > roomBounds.maxZ - roomBounds.playerRadius) {
            return false;
        }
        return !collisionBoxes.some((box) => wouldCollideWithBox(x, z, box));
    };

    const moveCameraWithCollision = (moveVector) => {
        const currentX = camera.position.x;
        const currentZ = camera.position.z;
        const targetX = currentX + moveVector.x;
        const targetZ = currentZ + moveVector.z;

        if (isWalkable(targetX, targetZ)) {
            camera.position.x = targetX;
            camera.position.z = targetZ;
            return;
        }

        // Sliding collision: if the diagonal is blocked, still allow the
        // unblocked axis. That makes door frames feel like walls instead of
        // sticky invisible boxes and prevents skipping through divider walls.
        if (isWalkable(targetX, currentZ)) {
            camera.position.x = targetX;
        }
        if (isWalkable(camera.position.x, targetZ)) {
            camera.position.z = targetZ;
        }
    };

    const resolveCameraInsideRoom = () => {
        camera.position.x = THREE.MathUtils.clamp(camera.position.x, roomBounds.minX + roomBounds.playerRadius, roomBounds.maxX - roomBounds.playerRadius);
        camera.position.y = roomBounds.playerY;
        camera.position.z = THREE.MathUtils.clamp(camera.position.z, roomBounds.minZ + roomBounds.playerRadius, roomBounds.maxZ - roomBounds.playerRadius);

        if (!isWalkable(camera.position.x, camera.position.z)) {
            camera.position.copy(startPosition);
        }
    };

    const clampCameraInsideRoom = resolveCameraInsideRoom;

    applyCameraRotation();
    clampCameraInsideRoom();

    const ambient = new THREE.AmbientLight('#d1b884', 1.38);
    scene.add(ambient);

    const key = new THREE.SpotLight('#ffe3aa', 4.35, 14.5, Math.PI / 5, 0.52, 1.05);
    key.position.set(-2.2, 5.15, 2.4);
    key.castShadow = true;
    key.shadow.mapSize.set(1024, 1024);
    scene.add(key);

    const rim = new THREE.PointLight('#9BE447', 1.58, 9.5, 1.45);
    rim.position.set(3.5, 1.85, -0.5);
    scene.add(rim);

    const gold = new THREE.PointLight('#D6A84F', 1.52, 8.8, 1.55);
    gold.position.set(-3.6, 1.55, -2.3);
    scene.add(gold);

    const focusTarget = new THREE.Object3D();
    focusTarget.position.set(0, 0.75, -4.0);
    scene.add(focusTarget);

    const focusSpot = new THREE.SpotLight('#D6A84F', 0, 4.8, Math.PI / 8, 0.42, 1.05);
    focusSpot.position.set(0, 2.65, -2.15);
    focusSpot.target = focusTarget;
    scene.add(focusSpot);
    scene.add(focusSpot.target);

    const floorMaterial = new THREE.MeshStandardMaterial({ color: '#201e19', roughness: 0.82, metalness: 0.05 });
    const wallMaterial = new THREE.MeshStandardMaterial({ color: '#28241c', roughness: 0.88, metalness: 0.04 });
    const trimMaterial = new THREE.MeshStandardMaterial({ color: '#4a3c27', roughness: 0.68, metalness: 0.20 });

    const addBox = (width, height, depth, x, y, z, material, options = {}) => {
        const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, height, depth), material);
        mesh.position.set(x, y, z);
        mesh.castShadow = options.castShadow ?? true;
        mesh.receiveShadow = options.receiveShadow ?? true;
        if (options.hideWhenEnvironment || options.hideWithCustomRoom !== false) {
            proceduralEnvironmentMeshes.push(mesh);
        }
        scene.add(mesh);
        return mesh;
    };

    const propGroups = [];
    const propModels = {
        barrel: null,
        lantern: null,
    };
    const proceduralEnvironmentMeshes = [];
    const environmentModels = [];
    const customRoomModels = [];
    const customRoomLightGroup = new THREE.Group();
    customRoomLightGroup.name = 'HNT_CustomRoom_CeilingLights';
    scene.add(customRoomLightGroup);
    let customRoomSlots = [];
    let trophyDisplays = [];

    const loadModel = (url, onProgress = null) => new Promise((resolve, reject) => {
        if (!url) {
            reject(new Error('Missing GLB model URL.'));
            return;
        }
        const loader = new GLTFLoader();
        loader.load(
            url,
            (gltf) => resolve(gltf.scene),
            (event) => {
                if (typeof onProgress !== 'function' || !event) {
                    return;
                }

                if (event.lengthComputable && event.total > 0) {
                    onProgress((event.loaded / event.total) * 100);
                } else if (event.loaded > 0) {
                    onProgress(18);
                }
            },
            reject,
        );
    });

    const applyLoadedPropsToGroup = (group) => {
        if (!group || group.userData.hasGlbProps) {
            return;
        }

        let didReplace = false;

        if (propModels.barrel) {
            const barrelModel = createNormalizedModelClone(propModels.barrel, 1.06);
            if (barrelModel) {
                barrelModel.position.set(0, -0.18, 0);
                barrelModel.rotation.y = group.userData.seedRotation || 0;
                group.add(barrelModel);
                didReplace = true;
            }
        }

        if (propModels.lantern) {
            const lanternModel = createNormalizedModelClone(propModels.lantern, 0.20);
            if (lanternModel) {
                lanternModel.position.set(0, 0.84, 0);
                lanternModel.rotation.y = (group.userData.seedRotation || 0) + Math.PI * 0.08;
                group.add(lanternModel);
                didReplace = true;
            }
        }

        if (didReplace) {
            group.children.forEach((child) => {
                if (child.userData?.isFallbackProp) {
                    child.visible = false;
                }
            });
            group.userData.hasGlbProps = true;
        }
    };

    const loadPropModels = () => {
        Promise.allSettled([
            loadModel(root.dataset.modelBarrel).then((model) => { propModels.barrel = model; }),
            loadModel(root.dataset.modelLantern).then((model) => { propModels.lantern = model; }),
        ]).then(() => {
            propGroups.forEach(applyLoadedPropsToGroup);
        }).catch(() => {
            // Fallback geometry remains visible when GLB loading fails.
        });
    };



    const hideDeep = (object) => {
        if (!object) {
            return;
        }

        object.visible = false;
        object.traverse?.((child) => {
            child.visible = false;
        });
    };

    const findNamedObject = (object, matcher) => {
        let match = null;
        object.traverse((child) => {
            if (!match && matcher(String(child.name || ''))) {
                match = child;
            }
        });

        return match;
    };

    const objectWorldBox = (object) => {
        const box = new THREE.Box3().setFromObject(object);

        return Number.isFinite(box.min.x) && Number.isFinite(box.max.x) ? box : null;
    };

    const applyFloorBoundsFromObject = (object) => {
        const box = objectWorldBox(object);

        if (!box) {
            return;
        }

        roomBounds.minX = box.min.x + 0.28;
        roomBounds.maxX = box.max.x - 0.28;
        roomBounds.minZ = box.min.z + 0.28;
        roomBounds.maxZ = box.max.z - 0.28;
    };

    const addCollisionBoxFromObject = (object) => {
        const box = objectWorldBox(object);

        if (!box) {
            return;
        }

        const width = box.max.x - box.min.x;
        const depth = box.max.z - box.min.z;
        const height = box.max.y - box.min.y;

        if (height < 0.65) {
            return;
        }

        // Avoid turning broad decorative scans into full blockers. Intended
        // collision objects are the Godot BoxMesh walls/dividers named by the user.
        if (width < 0.08 || depth < 0.08) {
            return;
        }

        collisionBoxes.push({
            minX: box.min.x,
            maxX: box.max.x,
            minZ: box.min.z,
            maxZ: box.max.z,
        });
    };

    const addCustomRoomCeilingLights = () => {
        customRoomLightGroup.clear();

        const minZ = Number.isFinite(roomBounds.minZ) ? roomBounds.minZ : -18;
        const maxZ = Number.isFinite(roomBounds.maxZ) ? roomBounds.maxZ : 2;
        const depth = Math.max(6, maxZ - minZ);
        const ceilingY = 3.15;
        const lightPositions = [
            maxZ - depth * 0.18,
            maxZ - depth * 0.50,
            maxZ - depth * 0.82,
        ];

        lightPositions.forEach((z, index) => {
            const isFinalRoom = index === lightPositions.length - 1;
            const warmth = isFinalRoom ? '#ffd08a' : '#f2c274';
            const point = new THREE.PointLight(warmth, isFinalRoom ? 4.8 : 3.35, isFinalRoom ? 10.5 : 8.8, 1.15);
            point.position.set(0, ceilingY, z);
            point.castShadow = false;
            customRoomLightGroup.add(point);

            const target = new THREE.Object3D();
            target.position.set(0, 1.2, z - 0.8);
            customRoomLightGroup.add(target);

            const spot = new THREE.SpotLight(warmth, isFinalRoom ? 3.9 : 2.75, isFinalRoom ? 12.5 : 9.5, Math.PI / 3.2, 0.68, 1.0);
            spot.position.set(0, ceilingY + 0.15, z);
            spot.target = target;
            spot.castShadow = false;
            customRoomLightGroup.add(spot);
        });
    };

    const extractTrophySlotsFromModel = (model) => {
        const slots = [];

        model.updateMatrixWorld(true);
        model.traverse((child) => {
            const name = String(child.name || '');

            if (!name.startsWith('TrophySlot_')) {
                return;
            }

            const position = new THREE.Vector3();
            child.getWorldPosition(position);

            // Godot exports the marker position from the object's origin. For
            // BoxMesh markers this is usually the center of the box, while the
            // useful placement point for our trophies is the marker's lower
            // contact surface on the shelf. Using the bounding-box bottom makes
            // trophies sit on the user's placed marker instead of floating.
            const slotBox = new THREE.Box3().setFromObject(child);
            if (Number.isFinite(slotBox.min.y) && Number.isFinite(slotBox.max.y) && slotBox.max.y > slotBox.min.y) {
                position.y = slotBox.min.y - 0.015;
            }

            slots.push({ name, position });

            hideDeep(child);
        });

        return slots.sort((left, right) => left.name.localeCompare(right.name, undefined, { numeric: true }));
    };

    const applyTrophySlots = () => {
        if (!customRoomSlots.length || !trophyDisplays.length) {
            return;
        }

        trophyDisplays.forEach((display, index) => {
            const slot = customRoomSlots[index % customRoomSlots.length];

            if (!slot) {
                return;
            }

            placeTrophyDisplay(display, slot.position, true);
        });
    };

    const applyCustomRoomEnvironment = (model) => {
        if (!model || customRoomModels.length) {
            return;
        }

        const environment = prepareLoadedModel(model, { castShadow: true, receiveShadow: true });
        environment.userData.isCustomRoomEnvironment = true;
        scene.add(environment);
        customRoomModels.push(environment);
        environment.updateMatrixWorld(true);

        const floorObject = findNamedObject(environment, (name) => name === 'Floor' || name.startsWith('Floor_'));
        if (floorObject) {
            applyFloorBoundsFromObject(floorObject);
        }

        collisionBoxes.length = 0;
        environment.traverse((child) => {
            const name = String(child.name || '');

            if (/^(Wall|Divider)/i.test(name)) {
                addCollisionBoxFromObject(child);
            }

            if (/PlayerPreviewCamera/i.test(name)) {
                const spawn = new THREE.Vector3();
                child.getWorldPosition(spawn);
                startPosition.set(spawn.x, roomBounds.playerY, spawn.z);
                camera.position.copy(startPosition);
            }

            if (child.isCamera) {
                child.visible = false;
            }
        });

        addCustomRoomCeilingLights();

        customRoomSlots = extractTrophySlotsFromModel(environment);
        applyTrophySlots();

        proceduralEnvironmentMeshes.forEach((mesh) => {
            mesh.visible = false;
        });
        propGroups.forEach((group) => {
            group.visible = false;
        });
        environmentModels.forEach((legacyEnvironment) => {
            legacyEnvironment.visible = false;
        });
    };

    const loadCustomRoomModel = () => {
        if (!root.dataset.modelCustomRoom) {
            finishRoomLoading();
            return;
        }

        loadModel(root.dataset.modelCustomRoom, (percent) => {
            const label = percent >= 98
                ? (root.dataset.loadingPreparingLabel || 'Raum wird vorbereitet')
                : (root.dataset.loadingProgressLabel || root.dataset.loadingLabel || 'Raum wird geladen');
            setRoomLoading(Math.min(percent, 98), label);
        })
            .then((model) => {
                setRoomLoading(99, root.dataset.loadingPreparingLabel || 'Raum wird vorbereitet');
                applyCustomRoomEnvironment(model);
                finishRoomLoading();
            })
            .catch(() => {
                // Procedural room remains visible when the custom room GLB fails.
                finishRoomLoading();
            });
    };

    const prepareLoadedModel = (model, { castShadow = true, receiveShadow = true } = {}) => {
        model.traverse((child) => {
            if (child.isMesh) {
                child.castShadow = castShadow;
                child.receiveShadow = receiveShadow;
                if (child.material && child.material.clone) {
                    child.material = child.material.clone();
                    child.material.needsUpdate = true;
                }
            }
        });
        return model;
    };

    const fitModelToHall = (source, hall, options = {}) => {
        if (!source) {
            return null;
        }

        const model = prepareLoadedModel(source.clone(true), { castShadow: false, receiveShadow: true });
        const initialBox = new THREE.Box3().setFromObject(model);
        const initialSize = new THREE.Vector3();
        initialBox.getSize(initialSize);

        if (!Number.isFinite(initialSize.x) || !Number.isFinite(initialSize.y) || !Number.isFinite(initialSize.z)
            || initialSize.x <= 0.0001 || initialSize.y <= 0.0001 || initialSize.z <= 0.0001) {
            return null;
        }

        const maxWidth = options.maxWidth || 9.20;
        const maxDepth = options.maxDepth || 5.45;
        const maxHeight = options.maxHeight || 3.02;
        const scale = Math.min(maxWidth / initialSize.x, maxDepth / initialSize.z, maxHeight / initialSize.y);
        model.scale.multiplyScalar(scale);

        const box = new THREE.Box3().setFromObject(model);
        const center = new THREE.Vector3();
        box.getCenter(center);

        model.position.x += -center.x;
        model.position.z += hall.centerZ - center.z;
        model.position.y += -0.30 - box.min.y;
        model.userData.isEnvironmentModel = true;
        return model;
    };

    const applyArmouryEnvironment = (model) => {
        if (!model || environmentModels.length) {
            return;
        }

        hallConfigs.forEach((hall) => {
            const environment = fitModelToHall(model, hall);
            if (environment) {
                scene.add(environment);
                environmentModels.push(environment);
            }
        });

        if (environmentModels.length) {
            proceduralEnvironmentMeshes.forEach((mesh) => {
                mesh.visible = false;
            });
        }
    };

    const loadEnvironmentModel = () => {
        if (!root.dataset.modelArmoury) {
            return;
        }

        loadModel(root.dataset.modelArmoury)
            .then((model) => applyArmouryEnvironment(model))
            .catch(() => {
                // Procedural room remains visible when the environment GLB fails.
            });
    };

    const pathMaterial = new THREE.MeshStandardMaterial({ color: '#30281a', roughness: 0.76, metalness: 0.08 });
    const barrelMaterial = new THREE.MeshStandardMaterial({ color: '#3a2719', roughness: 0.74, metalness: 0.08 });
    const barrelBandMaterial = new THREE.MeshStandardMaterial({ color: '#1a1713', roughness: 0.58, metalness: 0.28 });
    const lanternMaterial = new THREE.MeshStandardMaterial({
        color: '#f3b958',
        emissive: new THREE.Color('#d6a84f'),
        emissiveIntensity: 1.35,
        roughness: 0.34,
        metalness: 0.12,
    });

    const addBarrelLantern = (x, z, accent = '#D6A84F') => {
        const group = new THREE.Group();
        group.position.set(x, 0.03, z);
        group.userData.seedRotation = ((x * 17.31) + (z * 4.73)) % (Math.PI * 2);

        const barrel = new THREE.Mesh(new THREE.CylinderGeometry(0.40, 0.46, 0.88, 24), barrelMaterial);
        barrel.userData.isFallbackProp = true;
        barrel.position.y = 0.16;
        barrel.castShadow = true;
        barrel.receiveShadow = true;
        group.add(barrel);

        [-0.14, 0.16, 0.39].forEach((offset) => {
            const band = new THREE.Mesh(new THREE.CylinderGeometry(0.47, 0.47, 0.045, 24), barrelBandMaterial);
            band.userData.isFallbackProp = true;
            band.position.y = offset;
            band.castShadow = true;
            group.add(band);
        });

        const lantern = new THREE.Mesh(new THREE.BoxGeometry(0.095, 0.115, 0.095), lanternMaterial);
        lantern.userData.isFallbackProp = true;
        lantern.position.y = 0.82;
        lantern.castShadow = true;
        group.add(lantern);

        const lanternCap = new THREE.Mesh(new THREE.CylinderGeometry(0.052, 0.052, 0.020, 14), barrelBandMaterial);
        lanternCap.userData.isFallbackProp = true;
        lanternCap.position.y = 0.895;
        lanternCap.castShadow = true;
        group.add(lanternCap);

        const lanternGlowColor = '#D6A84F';
        const glowMaterial = new THREE.MeshBasicMaterial({
            color: new THREE.Color(lanternGlowColor),
            transparent: true,
            opacity: 0.14,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        });
        const glowOrb = new THREE.Mesh(new THREE.SphereGeometry(0.085, 20, 14), glowMaterial);
        glowOrb.position.y = 0.94;
        glowOrb.userData.isLanternGlow = true;
        group.add(glowOrb);

        const glow = new THREE.PointLight(lanternGlowColor, 1.38, 3.15, 1.46);
        glow.position.set(0, 0.92, 0);
        group.add(glow);

        scene.add(group);
        propGroups.push(group);
        applyLoadedPropsToGroup(group);
        return group;
    };

    loadPropModels();
    loadEnvironmentModel();
    loadCustomRoomModel();

    const floor = addBox(10.55, 0.12, 20.65, 0, -0.28, -7.82, floorMaterial, { castShadow: false, hideWhenEnvironment: true });

    // Outer shell: front/back/side walls plus a low dark ceiling, so the
    // trophy halls feel like closed rooms and the player cannot look outside.
    addBox(10.55, 3.55, 0.12, 0, 1.36, -18.18, wallMaterial, { castShadow: false, hideWhenEnvironment: true });
    addBox(10.55, 3.55, 0.12, 0, 1.36, 2.08, wallMaterial, { castShadow: false, hideWhenEnvironment: true });
    addBox(0.12, 3.45, 20.55, -5.22, 1.31, -7.82, wallMaterial, { castShadow: false, hideWhenEnvironment: true });
    addBox(0.12, 3.45, 20.55, 5.22, 1.31, -7.82, wallMaterial, { castShadow: false, hideWhenEnvironment: true });
    addBox(10.55, 0.12, 20.65, 0, 3.16, -7.82, wallMaterial, { castShadow: false, receiveShadow: false, hideWhenEnvironment: true });

    const ceilingBeamMaterial = new THREE.MeshStandardMaterial({ color: '#2c281f', roughness: 0.74, metalness: 0.12 });
    for (let i = -8; i <= 8; i += 1) {
        addBox(0.016, 0.014, 20.35, i * 0.55, -0.205, -7.82, trimMaterial, { castShadow: false });
    }

    for (let i = 0; i < 6; i += 1) {
        addBox(0.12, 0.16, 20.1, -4.4 + i * 1.76, 3.02, -7.82, ceilingBeamMaterial, { hideWhenEnvironment: true });
    }

    addBox(2.42, 0.026, 18.75, 0, -0.178, -8.05, pathMaterial, { castShadow: false, receiveShadow: true, hideWhenEnvironment: true });
    addBox(0.035, 0.034, 18.55, -1.28, -0.152, -8.05, trimMaterial, { castShadow: false, hideWhenEnvironment: true });
    addBox(0.035, 0.034, 18.55, 1.28, -0.152, -8.05, trimMaterial, { castShadow: false, hideWhenEnvironment: true });

    hallConfigs.forEach((hall) => {
        addBarrelLantern(-4.50, hall.centerZ - 1.92, hall.accent);
        addBarrelLantern(4.50, hall.centerZ - 1.92, hall.accent);
        addBarrelLantern(-4.50, hall.centerZ + 1.62, hall.accent);
        addBarrelLantern(4.50, hall.centerZ + 1.62, hall.accent);
    });

    const addDivider = (z, label, accent) => {
        const wallMinX = -5.20;
        const wallMaxX = 5.20;
        const doorHalf = roomBounds.doorHalfWidth;
        const leftWidth = Math.max(0.2, doorHalf - wallMinX);
        const rightWidth = Math.max(0.2, wallMaxX - doorHalf);
        const leftCenter = (wallMinX + -doorHalf) / 2;
        const rightCenter = (doorHalf + wallMaxX) / 2;

        // Real wall pieces with a clearly open doorway in the middle. The
        // center remains empty from floor to head beam, so the next hall is
        // visibly reachable instead of looking like a solid partition.
        addBox(leftWidth, 3.10, 0.14, leftCenter, 1.30, z, wallMaterial, { castShadow: false });
        addBox(rightWidth, 3.10, 0.14, rightCenter, 1.30, z, wallMaterial, { castShadow: false });

        const lintelWidth = doorHalf * 2 + 0.36;
        addBox(wallMaxX - wallMinX, 0.22, 0.16, 0, 2.98, z, trimMaterial);
        addBox(lintelWidth, 0.34, 0.18, 0, 2.28, z + 0.018, trimMaterial);
        addBox(0.22, 2.30, 0.20, -doorHalf, 0.88, z + 0.018, trimMaterial);
        addBox(0.22, 2.30, 0.20, doorHalf, 0.88, z + 0.018, trimMaterial);

        // Bright threshold and floor runner make the passage obvious even in
        // the dark bayou-style lighting.
        addBox(doorHalf * 2 - 0.28, 0.025, 1.16, 0, -0.185, z, pathMaterial, { castShadow: false, receiveShadow: true });
        addBox(0.035, 0.035, 1.08, -doorHalf + 0.18, -0.155, z, trimMaterial, { castShadow: false });
        addBox(0.035, 0.035, 1.08, doorHalf - 0.18, -0.155, z, trimMaterial, { castShadow: false });

        const doorGlow = new THREE.PointLight(accent, 1.10, 4.2, 1.45);
        doorGlow.position.set(0, 1.55, z + 0.10);
        scene.add(doorGlow);

        addBarrelLantern(-doorHalf - 0.55, z + 0.46, accent);
        addBarrelLantern(doorHalf + 0.55, z + 0.46, accent);
        addBarrelLantern(-doorHalf - 0.55, z - 0.46, accent);
        addBarrelLantern(doorHalf + 0.55, z - 0.46, accent);

        const doorwayPlaque = createWallPlaque(label, accent);
        if (doorwayPlaque) {
            doorwayPlaque.position.set(0, 2.55, z + 0.102);
            doorwayPlaque.scale.set(0.92, 0.92, 0.92);
            scene.add(doorwayPlaque);
        }
    };

    const addDisplayWall = (hall) => {
        const hasDoorway = hall.key !== 'legendary';
        const wallZ = hall.displayZ;
        const doorHalf = roomBounds.doorHalfWidth;
        const sideSections = [
            { centerX: -3.42, width: 2.92, startX: -4.54 },
            { centerX: 3.42, width: 2.92, startX: 2.30 },
        ];

        if (hasDoorway) {
            sideSections.forEach((section) => {
                addBox(section.width, 2.12, 0.04, section.centerX, 1.38, wallZ - 0.085, trimMaterial, { castShadow: false });

                for (let i = 0; i < 3; i += 1) {
                    addBox(section.width, 0.10, 0.42, section.centerX, 0.48 + i * 0.72, wallZ + 0.08, trimMaterial);
                }

                for (let i = 0; i < 3; i += 1) {
                    addBox(0.12, 2.08, 0.08, section.startX + i * 1.12, 1.38, wallZ - 0.055, trimMaterial, { castShadow: false });
                }
            });

            // The previous display wall visually blocked the path. This builds
            // the trophy shelves only on the left/right side and leaves a real,
            // floor-to-ceiling center opening so the next hall is visible.
            addBox(0.18, 2.36, 0.18, -doorHalf, 0.96, wallZ + 0.02, trimMaterial);
            addBox(0.18, 2.36, 0.18, doorHalf, 0.96, wallZ + 0.02, trimMaterial);
            addBox(doorHalf * 2 + 0.42, 0.24, 0.20, 0, 2.38, wallZ + 0.02, trimMaterial);
            addBox(doorHalf * 2 - 0.18, 0.035, 0.92, 0, -0.155, wallZ + 0.02, pathMaterial, { castShadow: false, receiveShadow: true });

            const openingGlow = new THREE.PointLight(hall.accent, 1.45, 5.2, 1.35);
            openingGlow.position.set(0, 1.40, wallZ - 0.15);
            scene.add(openingGlow);
        } else {
            for (let i = 0; i < 6; i += 1) {
                addBox(1.28, 2.12, 0.035, -3.75 + i * 1.5, 1.38, wallZ - 0.085, trimMaterial, { castShadow: false });
            }

            for (let i = 0; i < 3; i += 1) {
                addBox(8.55, 0.10, 0.42, 0, 0.48 + i * 0.72, wallZ + 0.08, trimMaterial);
            }
        }

        const plaque = createWallPlaque(hall.title, hall.accent);
        if (plaque) {
            plaque.position.set(hasDoorway ? -3.35 : 0, 2.62, wallZ + 0.105);
            plaque.scale.set(hasDoorway ? 0.74 : 0.92, hasDoorway ? 0.74 : 0.92, hasDoorway ? 0.74 : 0.92);
            scene.add(plaque);
        }

        if (hasDoorway) {
            const nextLabel = createWallPlaque(root.dataset[`room${hall.key === 'starter' ? 'Rare' : 'Legendary'}`] || (hall.key === 'starter' ? 'Rare & Epic Hall' : 'Legendary Hall'), hall.accent);
            if (nextLabel) {
                nextLabel.position.set(0, 1.92, wallZ - 0.34);
                nextLabel.scale.set(0.56, 0.56, 0.56);
                scene.add(nextLabel);
            }
        }

        const hallGlow = new THREE.PointLight(hall.accent, 0.78, 6.6, 1.62);
        hallGlow.position.set(0, 2.0, hall.centerZ - 0.4);
        scene.add(hallGlow);
    };

    hallConfigs.forEach((hall) => addDisplayWall({
        ...hall,
        title: root.dataset[`room${hall.key.charAt(0).toUpperCase()}${hall.key.slice(1)}`] || hall.title,
    }));

    addDivider(dividerWalls[0].z, root.dataset.roomRare || 'Rare & Epic Hall', '#7FB069');
    addDivider(dividerWalls[1].z, root.dataset.roomLegendary || 'Legendary Hall', '#D6A84F');

    const clickable = [];
    const pedestalMaterial = new THREE.MeshStandardMaterial({ color: '#242017', roughness: 0.62, metalness: 0.24 });

    const placeTrophyDisplay = (display, anchor, useCustomSlot = false) => {
        if (!display || !anchor) {
            return;
        }

        const x = anchor.x ?? anchor[0] ?? 0;
        const y = anchor.y ?? anchor[1] ?? 1;
        const z = anchor.z ?? anchor[2] ?? 0;

        if (useCustomSlot) {
            display.pedestal.visible = false;
            display.ring.visible = false;
            display.mesh.scale.setScalar(0.36);
            display.mesh.position.set(x, y, z);
            display.mesh.updateMatrixWorld(true);

            const trophyBox = new THREE.Box3().setFromObject(display.mesh);
            if (Number.isFinite(trophyBox.min.y) && Number.isFinite(trophyBox.max.y)) {
                display.mesh.position.y += (y - trophyBox.min.y) + 0.01;
            }

            display.mesh.updateMatrixWorld(true);
            const alignedBox = new THREE.Box3().setFromObject(display.mesh);
            const topY = Number.isFinite(alignedBox.max.y) ? alignedBox.max.y : (display.mesh.position.y + 0.35);

            if (display.label) {
                display.label.position.set(x, y - 0.03, z + 0.22);
                display.label.scale.set(0.38, 0.10, 1);
            }
            display.trophyLight.position.set(x, topY + 0.18, z + 0.12);
            display.trophyLight.intensity = 0.62;
            display.trophyLight.distance = 2.0;
        } else {
            display.pedestal.visible = true;
            display.ring.visible = true;
            display.pedestal.position.set(x, y - 0.40, z);
            display.ring.position.set(x, y - 0.285, z);
            display.mesh.position.set(x, y - 0.16, z);
            display.mesh.scale.setScalar(1);
            if (display.label) {
                display.label.position.set(x, y - 0.62, z + 0.27);
                display.label.scale.set(0.86, 0.22, 1);
            }
            display.trophyLight.position.set(x, y + 0.35, z + 0.18);
        }

        display.mesh.userData.baseY = display.mesh.position.y;
    };

    trophies.forEach((trophy, index) => {
        const position = Array.isArray(trophy.position) ? trophy.position : [index - 2, 1, -4.0];
        const pedestal = new THREE.Mesh(new THREE.CylinderGeometry(0.36, 0.43, 0.20, 32), pedestalMaterial);
        pedestal.castShadow = true;
        pedestal.receiveShadow = true;
        scene.add(pedestal);

        const ring = createSpotRing(trophy.accent || '#D6A84F');
        scene.add(ring);

        const mesh = createTrophyMesh(trophy);
        mesh.userData.trophy = trophy;
        mesh.userData.ring = ring;
        mesh.userData.isSelected = index === 0;
        mesh.traverse((child) => {
            if (child.isMesh) {
                child.castShadow = true;
                child.receiveShadow = true;
                child.userData.trophy = trophy;
                child.userData.parentTrophy = mesh;
            }
        });
        scene.add(mesh);

        // In-world nameplates are intentionally disabled in the custom room.
        // The player gets the trophy title/details through E/click interaction
        // and the side panel, so the shelves stay visually clean.
        const label = null;

        const trophyLight = new THREE.PointLight(trophy.accent || '#D6A84F', 0.36, 2.4, 1.7);
        scene.add(trophyLight);

        const display = {
            trophy,
            pedestal,
            ring,
            mesh,
            label,
            trophyLight,
        };
        trophyDisplays.push(display);
        placeTrophyDisplay(display, new THREE.Vector3(position[0], position[1], position[2]), false);

        clickable.push(mesh);
    });

    applyTrophySlots();

    const raycaster = new THREE.Raycaster();
    const pointer = new THREE.Vector2();
    let selected = clickable[0] || null;
    let hovered = null;
    let infoMesh = null;
    let pointerDown = null;

    const applyTrophyState = () => {
        clickable.forEach((mesh) => {
            const isActive = mesh === selected;
            const isHover = mesh === hovered;
            const ring = mesh.userData.ring;
            const label = mesh.userData.label;

            mesh.userData.isSelected = isActive;
            if (ring?.material) {
                ring.material.opacity = isActive ? 0.42 : (isHover ? 0.28 : ring.userData.baseOpacity || 0.12);
            }
            if (label?.material) {
                label.material.opacity = isActive ? 1 : (isHover ? 0.96 : 0.72);
            }
        });

        const target = hovered || selected;
        if (target?.userData?.trophy) {
            root.style.setProperty('--trophy-accent', target.userData.trophy.accent || '#D6A84F');
        }
        root.classList.toggle('has-ingame-trophy-hover', Boolean((isPointerLocked || prefersTouchControls) && hovered));
        updateFocusLight(target);
        updateIngameCard(infoMesh);
    };

    const updateDetailPanel = (trophy) => {
        setText('[data-trophy-detail-title]', trophy?.title || root.dataset.emptyTitle || 'Trophy Room');
        setText('[data-trophy-detail-description]', trophy?.description || root.dataset.emptyText || 'Select a trophy.');
        setText('[data-trophy-detail-subtitle]', trophy?.subtitle || '—');
        setText('[data-trophy-detail-rarity]', trophy?.rarity || '—');
        setText('[data-trophy-detail-meta]', trophy?.meta || '—');
        document.querySelectorAll('[data-trophy-detail-panel]').forEach((panel) => {
            panel.style.setProperty('--trophy-accent', trophy?.accent || '#D6A84F');
        });
    };

    const updateIngameCard = (mesh) => {
        const trophy = mesh?.userData?.trophy || null;

        if (!ingameCard || !ingameTitle || !ingameMeta) {
            return;
        }

        // The small in-room info card should behave like an interaction prompt:
        // it appears only after clicking / pressing E on the currently aimed trophy
        // and disappears as soon as the player looks away from that trophy.
        if ((!isPointerLocked && !prefersTouchControls) || !trophy || mesh !== hovered) {
            ingameCard.hidden = true;
            return;
        }

        ingameTitle.textContent = trophy.title || root.dataset.emptyTitle || 'Trophy';
        ingameMeta.textContent = [trophy.subtitle, trophy.rarity, trophy.meta]
            .filter(Boolean)
            .slice(0, 2)
            .join(' · ') || '—';
        ingameCard.style.setProperty('--trophy-accent', trophy.accent || '#D6A84F');
        ingameCard.hidden = false;
    };

    const updateFocusLight = (mesh) => {
        const trophy = mesh?.userData?.trophy || null;

        if (!mesh || !trophy) {
            focusSpot.intensity = 0;
            return;
        }

        const accent = new THREE.Color(trophy.accent || '#D6A84F');
        focusSpot.color.copy(accent);
        focusSpot.position.set(mesh.position.x, mesh.position.y + 1.35, mesh.position.z + 1.15);
        focusTarget.position.set(mesh.position.x, mesh.position.y + 0.18, mesh.position.z);
        focusSpot.intensity = mesh === selected ? 2.9 : 2.15;
    };

    const findPickedAtPointer = (event) => {
        const rect = renderer.domElement.getBoundingClientRect();
        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        raycaster.setFromCamera(pointer, camera);
        const intersects = raycaster.intersectObjects(clickable, true);
        return intersects.length ? (intersects[0].object.userData.parentTrophy || intersects[0].object) : null;
    };

    const findPickedAtCenter = () => {
        pointer.set(0, 0);
        raycaster.setFromCamera(pointer, camera);
        const intersects = raycaster.intersectObjects(clickable, true);
        return intersects.length ? (intersects[0].object.userData.parentTrophy || intersects[0].object) : null;
    };

    const selectTrophy = (picked) => {
        if (!picked) {
            return false;
        }
        selected = picked;
        hovered = picked;
        infoMesh = picked;
        updateDetailPanel(picked.userData.trophy);
        applyTrophyState();
        updateIngameCard(infoMesh);
        return true;
    };

    const updateHoverAtPointer = (event) => {
        if (isPointerLocked) {
            return;
        }
        const picked = findPickedAtPointer(event);
        hovered = picked;
        renderer.domElement.style.cursor = picked ? 'pointer' : 'grab';
        applyTrophyState();
    };

    const updateHoverAtCenter = () => {
        if (!isPointerLocked && !prefersTouchControls) {
            return;
        }
        const picked = findPickedAtCenter();
        if (picked !== hovered) {
            hovered = picked;
            applyTrophyState();
        }
    };

    const requestPointerLock = () => {
        if (prefersTouchControls) {
            return;
        }
        if (document.pointerLockElement === canvas) {
            return;
        }
        if (typeof canvas.requestPointerLock === 'function') {
            canvas.requestPointerLock();
        }
    };

    const updateLockUi = () => {
        isPointerLocked = document.pointerLockElement === canvas;
        root.classList.toggle('is-pointer-locked', isPointerLocked);
        if (lockPrompt) {
            lockPrompt.hidden = isPointerLocked || prefersTouchControls;
        }
        if (status) {
            if (isRoomLoading) {
                status.innerHTML = '<i class="ph ph-spinner-gap" aria-hidden="true"></i>' + (root.dataset.loadingLabel || 'Raum wird geladen');
            } else if (prefersTouchControls) {
                status.innerHTML = '<i class="ph ph-game-controller" aria-hidden="true"></i>' + (root.dataset.touchLabel || 'Touch-Steuerung aktiv');
            } else if (isPointerLocked) {
                status.innerHTML = '<i class="ph ph-crosshair" aria-hidden="true"></i>' + (root.dataset.lockedLabel || 'Steuerung aktiv');
            } else {
                const readyText = trophies.length
                    ? (root.dataset.readyLabel || ':count trophies').replace(':count', trophies.length.toString())
                    : (root.dataset.readyEmpty || 'Ready');
                status.innerHTML = '<i class="ph ph-check-circle" aria-hidden="true"></i>' + readyText;
            }
        }
        updateIngameCard(isPointerLocked ? infoMesh : null);
    };

    const setFullscreenCanvasSizing = () => {
        const isFullscreen = document.fullscreenElement === canvas.parentElement;
        root.classList.toggle('is-trophy-fullscreen', isFullscreen);

        if (isFullscreen && canvas.parentElement) {
            canvas.parentElement.style.width = '100vw';
            canvas.parentElement.style.height = '100vh';
            canvas.style.minHeight = '0';
            canvas.style.height = '100%';
        } else if (canvas.parentElement) {
            canvas.parentElement.style.width = '';
            canvas.parentElement.style.height = '';
            canvas.style.minHeight = '';
            canvas.style.height = '';
        }

        if (fullscreenButton) {
            const icon = isFullscreen ? 'ph ph-corners-in' : 'ph ph-corners-out';
            const label = isFullscreen
                ? (root.dataset.fullscreenExitLabel || 'Vollbild verlassen')
                : (root.dataset.fullscreenLabel || 'Vollbild');
            fullscreenButton.innerHTML = `<i class="${icon}" aria-hidden="true"></i>${label}`;
        }

        updateSize();
    };

    const toggleFullscreen = () => {
        const target = canvas.parentElement;

        if (!target || !document.fullscreenEnabled) {
            return;
        }

        if (document.fullscreenElement === target) {
            document.exitFullscreen?.();
            return;
        }

        target.requestFullscreen?.();
    };

    const resetPlayer = () => {
        selected = clickable[0] || null;
        hovered = null;
        infoMesh = null;
        camera.position.copy(startPosition);
        yaw = startYaw;
        pitch = startPitch;
        applyCameraRotation();
        clampCameraInsideRoom();
        updateDetailPanel(selected?.userData?.trophy || null);
        applyTrophyState();
        updateIngameCard(null);
    };

    const updateSize = () => {
        const box = canvas.parentElement?.getBoundingClientRect();
        if (!box) {
            return;
        }
        const width = Math.max(320, Math.floor(box.width));
        const height = Math.max(360, Math.floor(box.height));
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
    };

    canvas.addEventListener('pointermove', updateHoverAtPointer, { passive: true });
    canvas.addEventListener('pointerdown', (event) => {
        pointerDown = { x: event.clientX, y: event.clientY };
        if (!isPointerLocked) {
            renderer.domElement.style.cursor = 'grabbing';
        }
    }, { passive: true });
    canvas.addEventListener('pointerup', (event) => {
        if (isPointerLocked) {
            selectTrophy(findPickedAtCenter());
            pointerDown = null;
            return;
        }
        const deltaX = pointerDown ? Math.abs(event.clientX - pointerDown.x) : 0;
        const deltaY = pointerDown ? Math.abs(event.clientY - pointerDown.y) : 0;
        renderer.domElement.style.cursor = hovered ? 'pointer' : 'grab';
        if (deltaX < 8 && deltaY < 8) {
            const picked = findPickedAtPointer(event);
            if (!selectTrophy(picked) && !prefersTouchControls) {
                requestPointerLock();
            }
        }
        pointerDown = null;
    }, { passive: true });

    const resetTouchJoystick = () => {
        touchState.moveX = 0;
        touchState.moveY = 0;
        touchState.joystickPointer = null;
        if (touchJoystickKnob) {
            touchJoystickKnob.style.transform = 'translate(-50%, -50%)';
        }
    };

    const updateJoystickFromEvent = (event) => {
        if (!touchJoystick || !touchJoystickKnob) {
            return;
        }

        const rect = touchJoystick.getBoundingClientRect();
        const radius = Math.max(34, Math.min(rect.width, rect.height) * 0.36);
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        let dx = event.clientX - centerX;
        let dy = event.clientY - centerY;
        const length = Math.hypot(dx, dy);

        if (length > radius) {
            dx = (dx / length) * radius;
            dy = (dy / length) * radius;
        }

        touchState.moveX = THREE.MathUtils.clamp(dx / radius, -1, 1);
        touchState.moveY = THREE.MathUtils.clamp(-dy / radius, -1, 1);
        touchJoystickKnob.style.transform = `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px))`;
    };

    if (prefersTouchControls && touchJoystick) {
        touchJoystick.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            touchState.joystickPointer = event.pointerId;
            touchJoystick.setPointerCapture?.(event.pointerId);
            updateJoystickFromEvent(event);
        });
        touchJoystick.addEventListener('pointermove', (event) => {
            if (touchState.joystickPointer !== event.pointerId) {
                return;
            }
            event.preventDefault();
            updateJoystickFromEvent(event);
        });
        ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((eventName) => {
            touchJoystick.addEventListener(eventName, (event) => {
                if (eventName !== 'lostpointercapture' && touchState.joystickPointer !== event.pointerId) {
                    return;
                }
                resetTouchJoystick();
            });
        });
    }

    if (prefersTouchControls && touchLook) {
        touchLook.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            touchState.lookPointer = event.pointerId;
            touchState.lookLastX = event.clientX;
            touchState.lookLastY = event.clientY;
            touchLook.setPointerCapture?.(event.pointerId);
        });
        touchLook.addEventListener('pointermove', (event) => {
            if (touchState.lookPointer !== event.pointerId) {
                return;
            }
            event.preventDefault();
            const dx = event.clientX - touchState.lookLastX;
            const dy = event.clientY - touchState.lookLastY;
            touchState.lookLastX = event.clientX;
            touchState.lookLastY = event.clientY;
            yaw -= dx * mouseSensitivity * 1.18;
            pitch -= dy * mouseSensitivity * 1.18;
            applyCameraRotation();
        });
        ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((eventName) => {
            touchLook.addEventListener(eventName, (event) => {
                if (eventName !== 'lostpointercapture' && touchState.lookPointer !== event.pointerId) {
                    return;
                }
                touchState.lookPointer = null;
            });
        });
    }

    touchInteract?.addEventListener('click', (event) => {
        event.preventDefault();
        selectTrophy(findPickedAtCenter());
    });

    enterButton?.addEventListener('click', requestPointerLock);
    resetButton?.addEventListener('click', resetPlayer);
    fullscreenButton?.addEventListener('click', toggleFullscreen);
    document.addEventListener('fullscreenchange', setFullscreenCanvasSizing);

    document.addEventListener('pointerlockchange', updateLockUi);
    document.addEventListener('mousemove', (event) => {
        if (!isPointerLocked) {
            return;
        }
        yaw -= event.movementX * mouseSensitivity;
        pitch -= event.movementY * mouseSensitivity;
        applyCameraRotation();
    });

    window.addEventListener('keydown', (event) => {
        const code = event.code;
        if (['KeyW', 'KeyA', 'KeyS', 'KeyD', 'ShiftLeft', 'ShiftRight'].includes(code)) {
            keys.add(code);
            if (isPointerLocked) {
                event.preventDefault();
            }
        }
        if (isPointerLocked && code === 'KeyE') {
            event.preventDefault();
            selectTrophy(findPickedAtCenter());
        }
    });

    window.addEventListener('keyup', (event) => {
        keys.delete(event.code);
    });

    window.addEventListener('blur', () => {
        keys.clear();
    });

    window.addEventListener('resize', updateSize, { passive: true });
    const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(updateSize) : null;
    if (resizeObserver && canvas.parentElement) {
        resizeObserver.observe(canvas.parentElement);
    }

    const movementForward = new THREE.Vector3();
    const movementRight = new THREE.Vector3();
    const movementDirection = new THREE.Vector3();
    const worldUp = new THREE.Vector3(0, 1, 0);

    const updateMovement = (delta) => {
        if (!isPointerLocked && !prefersTouchControls) {
            return;
        }

        movementDirection.set(0, 0, 0);

        // Use the real camera view direction instead of manually reconstructing
        // a vector from yaw. This keeps WASD aligned with the current mouse look:
        // W is always forward, S backward, A/D strafe left/right.
        camera.getWorldDirection(movementForward);
        movementForward.y = 0;
        if (movementForward.lengthSq() > 0) {
            movementForward.normalize();
        } else {
            movementForward.set(0, 0, -1);
        }

        movementRight.crossVectors(movementForward, worldUp);
        if (movementRight.lengthSq() > 0) {
            movementRight.normalize();
        } else {
            movementRight.set(1, 0, 0);
        }

        if (keys.has('KeyW')) {
            movementDirection.add(movementForward);
        }
        if (keys.has('KeyS')) {
            movementDirection.sub(movementForward);
        }
        if (keys.has('KeyD')) {
            movementDirection.add(movementRight);
        }
        if (keys.has('KeyA')) {
            movementDirection.sub(movementRight);
        }

        if (prefersTouchControls) {
            if (Math.abs(touchState.moveY) > 0.05) {
                movementDirection.addScaledVector(movementForward, touchState.moveY);
            }
            if (Math.abs(touchState.moveX) > 0.05) {
                movementDirection.addScaledVector(movementRight, touchState.moveX);
            }
        }

        if (movementDirection.lengthSq() === 0) {
            return;
        }

        movementDirection.normalize();
        const analogStrength = prefersTouchControls ? THREE.MathUtils.clamp(Math.hypot(touchState.moveX, touchState.moveY), 0.35, 1) : 1;
        const speed = ((keys.has('ShiftLeft') || keys.has('ShiftRight')) ? runSpeed : baseSpeed) * analogStrength;
        moveCameraWithCollision(movementDirection.multiplyScalar(speed * delta));
    };

    const clock = new THREE.Clock();
    const animate = () => {
        const delta = Math.min(clock.getDelta(), 0.045);
        const elapsed = clock.elapsedTime;

        updateMovement(delta);
        updateHoverAtCenter();

        clickable.forEach((mesh, index) => {
            mesh.rotation.y += 0.0035 + index * 0.00035;
            mesh.position.y = mesh.userData.baseY + Math.sin(elapsed * 1.1 + index) * 0.025;
            const ring = mesh.userData.ring;
            if (ring) {
                const pulse = mesh === selected ? 1 + Math.sin(elapsed * 2.4) * 0.045 : 1;
                ring.scale.setScalar(pulse);
            }
        });

        if (selected) {
            selected.rotation.y += 0.01;
        }

        clampCameraInsideRoom();
        renderer.render(scene, camera);
        requestAnimationFrame(animate);
    };

    updateSize();
    updateDetailPanel(trophies[0] || null);
    applyTrophyState();
    renderer.domElement.style.cursor = 'grab';
    updateLockUi();
    animate();
};

roots.forEach((root) => {
    initRoom(root);
});
