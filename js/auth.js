// Scene setup
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 2000);
const renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('canvas'), antialias: true, alpha: true });

renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setClearColor(0x000000);
renderer.shadowMap.enabled = true;
camera.position.z = 25;

// Create tunnel of tubes
const tubeGroup = new THREE.Group();
scene.add(tubeGroup);

function createTubeRing(radius, depth, rotation = 0) {
    const points = [];
    const segments = 64;
    
    for (let i = 0; i < segments; i++) {
        const angle = (i / segments) * Math.PI * 2;
        const x = Math.cos(angle) * radius;
        const y = Math.sin(angle) * radius;
        points.push(new THREE.Vector3(x, y, 0));
    }
    points.push(points[0]);
    
    const curve = new THREE.CatmullRomCurve3(points);
    const tubeGeometry = new THREE.TubeGeometry(curve, segments, 0.8, 8, true);
    
    const material = new THREE.MeshPhongMaterial({
        color: new THREE.Color().setHSL(Math.random() * 0.3 + 0.5, 1, 0.6),
        emissive: new THREE.Color().setHSL(Math.random() * 0.3 + 0.5, 1, 0.3),
        shininess: 100,
        wireframe: false
    });
    
    const tube = new THREE.Mesh(tubeGeometry, material);
    tube.castShadow = true;
    tube.receiveShadow = true;
    tube.position.z = depth;
    tube.rotation.z = rotation;
    
    return tube;
}

// Create multiple concentric tube rings
for (let i = 0; i < 15; i++) {
    const tube = createTubeRing(8 + i * 1.5, -50 + i * 7, i * 0.1);
    tubeGroup.add(tube);
}

// Create spinning particles along the tunnel
const particleGroup = new THREE.Group();
scene.add(particleGroup);

const particleGeometry = new THREE.IcosahedronGeometry(0.3, 2);

for (let i = 0; i < 300; i++) {
    const material = new THREE.MeshPhongMaterial({
        color: new THREE.Color().setHSL(Math.random() * 0.2 + 0.5, 1, 0.7),
        emissive: new THREE.Color().setHSL(Math.random() * 0.2 + 0.5, 1, 0.4),
        shininess: 80
    });
    
    const particle = new THREE.Mesh(particleGeometry, material);
    const angle = Math.random() * Math.PI * 2;
    const radius = 5 + Math.random() * 8;
    
    particle.position.set(
        Math.cos(angle) * radius,
        Math.sin(angle) * radius,
        -40 + Math.random() * 80
    );
    
    particle.scale.set(Math.random() * 0.6 + 0.3, Math.random() * 0.6 + 0.3, Math.random() * 0.6 + 0.3);
    particle.castShadow = true;
    
    particle.velocity = {
        z: Math.random() * 0.3 + 0.1
    };
    
    particleGroup.add(particle);
}

// Advanced lighting
const light1 = new THREE.PointLight(0x00ff88, 2, 200);
light1.position.set(20, 20, 20);
light1.castShadow = true;
scene.add(light1);

const light2 = new THREE.PointLight(0x64c8ff, 2, 200);
light2.position.set(-20, -20, 20);
light2.castShadow = true;
scene.add(light2);

const light3 = new THREE.PointLight(0xff0080, 1.5, 150);
light3.position.set(0, 30, -30);
light3.castShadow = true;
scene.add(light3);

const ambientLight = new THREE.AmbientLight(0xffffff, 0.15);
scene.add(ambientLight);

// Mouse tracking
let mouseX = 0;
let mouseY = 0;
document.addEventListener('mousemove', (e) => {
    mouseX = (e.clientX / window.innerWidth) * 2 - 1;
    mouseY = -(e.clientY / window.innerHeight) * 2 + 1;
});

let time = 0;

// Animation loop
function animate() {
    requestAnimationFrame(animate);
    time += 0.001;

    // Rotate tube group
    tubeGroup.rotation.z += 0.0005;
    tubeGroup.position.z += 0.05;
    if (tubeGroup.position.z > 100) tubeGroup.position.z = -100;

    // Update particles
    particleGroup.children.forEach((particle) => {
        particle.position.z += particle.velocity.z;
        particle.rotation.x += 0.005;
        particle.rotation.y += 0.008;

        if (particle.position.z > 50) {
            particle.position.z = -100;
        }

        // Particle glow intensity variation
        particle.material.emissive.multiplyScalar(0.98);
    });

    // Animate lights
    light1.position.x = 20 + Math.sin(time * 2) * 10;
    light1.position.y = 20 + Math.cos(time * 1.5) * 10;
    
    light2.position.x = -20 + Math.cos(time * 1.8) * 10;
    light2.position.y = -20 + Math.sin(time * 2.2) * 10;

    // Camera interaction
    camera.position.x += (mouseX * 3 - camera.position.x) * 0.08;
    camera.position.y += (mouseY * 3 - camera.position.y) * 0.08;
    camera.lookAt(0, 0, 0);

    renderer.render(scene, camera);
}

animate();

// Handle window resize
window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

// Handle role tab switching
document.querySelectorAll('.role-tab input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.role-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        this.closest('.role-tab').classList.add('active');
    });
});

// Handle Google login with role
document.getElementById('googleLoginBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const selectedRole = document.querySelector('input[name="role"]:checked');
    if (!selectedRole) {
        alert('Please select your role first');
        return;
    }
    document.getElementById('googleRole').value = selectedRole.value;
    document.getElementById('googleLoginForm').submit();
});

