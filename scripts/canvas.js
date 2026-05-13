const canvas = document.querySelector("#funnyBg");
const ctx = canvas.getContext("2d");

canvas.width = 768;
canvas.height = window.innerHeight-5;

// Config
const CONFIG = {
    imageScale: 0.33,
    spawnCount: 7,
    velocityRange: .1,
    rotationSpeed: .001,
    edgeBounce: true,
    spawnDelay: 300,

    // Part1, Part2, Part3 spawn frequency
    spawnWeights: [1, 2, 1]
};


const images = [];
let loaded = 0;

// Load images
for (let i = 1; i <= 3; i++) {
    const img = new Image();
    img.onload = () => loaded++;
    img.src = `../images/Part${i}.png`;
    images.push(img);
}

const spawned = [];

// Picture spawn weight
function pickWeightedImage() {
    const total = CONFIG.spawnWeights.reduce((a, b) => a + b, 0);

    let r = Math.random() * total;

    for (let i = 0; i < images.length; i++) {
        r -= CONFIG.spawnWeights[i];
        if (r <= 0) return images[i];
    }

    return images[images.length - 1];
}

// Spawning
function spawnBatch(count = CONFIG.spawnCount) {
    if (loaded < images.length) return;

    for (let i = 0; i < count; i++) {
        const img = pickWeightedImage();

        const width = img.naturalWidth * CONFIG.imageScale;
        const height = img.naturalHeight * CONFIG.imageScale;

        spawned.push({
            image: img,
            x: Math.random() * (canvas.width - width),
            y: Math.random() * (canvas.height - height),
            width,
            height,

            vx: (Math.random() - 0.5) * CONFIG.velocityRange,
            vy: (Math.random() - 0.5) * CONFIG.velocityRange,

            angle: Math.random() * Math.PI * 2,
            spin: (Math.random() - 0.5) * CONFIG.rotationSpeed
        });
    }
}

// Draw loop
function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (const obj of spawned) {
        obj.x += obj.vx;
        obj.y += obj.vy;
        obj.angle += obj.spin;

        if (CONFIG.edgeBounce) {
            if (obj.x < 0 || obj.x + obj.width > canvas.width) obj.vx *= -1;
            if (obj.y < 0 || obj.y + obj.height > canvas.height) obj.vy *= -1;
        }

        ctx.save();
        ctx.translate(obj.x + obj.width / 2, obj.y + obj.height / 2);
        ctx.rotate(obj.angle);
        ctx.drawImage(
            obj.image,
            -obj.width / 2,
            -obj.height / 2,
            obj.width,
            obj.height
        );
        ctx.restore();
    }

    requestAnimationFrame(draw);
}

// Starting
setTimeout(() => {
    spawnBatch();
}, CONFIG.spawnDelay);

draw();