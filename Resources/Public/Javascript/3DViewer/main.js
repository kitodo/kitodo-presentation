//Supported file formats: OBJ, DAE, FBX, PLY, IFC, STL, XYZ, JSON, 3DS, PCD, glTF

import * as THREE from './build/three.module.js';
import { TWEEN } from './js/jsm/libs/tween.module.min.js';

import Stats from './js/jsm/libs/stats.module.js';

import { OrbitControls } from './js/jsm/controls/OrbitControls.js';
import { TransformControls } from './js/jsm/controls/TransformControls.js';
import { GUI } from './node_modules/lil-gui/dist/lil-gui.esm.min.js';
import { FBXLoader } from './js/jsm/loaders/FBXLoader.js';
import { DDSLoader } from './js/jsm/loaders/DDSLoader.js';
import { MTLLoader } from './js/jsm/loaders/MTLLoader.js';
import { OBJLoader } from './js/jsm/loaders/OBJLoader.js';
import { GLTFLoader } from './js/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from './js/jsm/loaders/DRACOLoader.js';
import { KTX2Loader } from './js/jsm/loaders/KTX2Loader.js';
import { MeshoptDecoder } from './js/jsm/libs/meshopt_decoder.module.js';
import { IFCLoader } from './js/jsm/loaders/IFCLoader.js';
import { PLYLoader } from './js/jsm/loaders/PLYLoader.js';
import { ColladaLoader } from './js/jsm/loaders/ColladaLoader.js';
import { STLLoader } from './js/jsm/loaders/STLLoader.js';
import { XYZLoader } from './js/jsm/loaders/XYZLoader.js';
import { TDSLoader } from './js/jsm/loaders/TDSLoader.js';

/*if (supportedFormats.indexOf(extension.toUpperCase()) < 0) {
	return
}*/

let camera, scene, renderer, stats, controls, loader, ambientLight, dirLight, dirLightTarget;
let imported;
var mainObject = [];
var metadataContentTech;
var distanceGeometry = new THREE.Vector3();
let wisskiID = '';

const clock = new THREE.Clock();
const editor = true;
var FULLSCREEN = false;

let mixer;

const container = document.getElementById("DFG_3DViewer");
container.setAttribute("width", window.self.innerWidth);
container.setAttribute("height", window.self.innerHeight);
const supportedFormats = [ 'OBJ', 'DAE', 'FBX', 'PLY', 'IFC', 'STL', 'XYZ', 'PCD', 'JSON', '3DS', 'GLFT' ];
const originalPath = container.getAttribute("3d");
const proxyPath = container.getAttribute("proxy");
const wisskiUrl = container.getAttribute("wisski_url");
const filename = container.getAttribute("3d").split("/").pop();
const basename = filename.substring(0, filename.lastIndexOf('.'));
const extension = filename.substring(filename.lastIndexOf('.') + 1);	
const path = container.getAttribute("3d").substring(0, container.getAttribute("3d").lastIndexOf(filename));
const domain = "https://3d-repository.hs-mainz.de";
const uri = path.replace(domain+"/", "");
const loadedFile = basename + "." + extension;
var COPYRIGHTS = false;
const allowedFormats = ['obj', 'fbx', 'ply', 'dae', 'ifc', 'stl', 'xyz', 'pcd', 'json', '3ds'];
var EXIT_CODE=1;
var gridSize;
var clippingMode=false;
var elementsURL = wisskiUrl;
elementsURL = elementsURL.match("/export_xml_single/(.*)?page");
wisskiID = elementsURL[1];

var canvasText;

var spinnerContainer = document.createElement("div");
spinnerContainer.id = 'spinnerContainer';
spinnerContainer.className = 'spinnerContainer';
spinnerContainer.style.position = 'absolute';
spinnerContainer.style.left = '40%';
spinnerContainer.style.marginTop = '10px';
spinnerContainer.style.zIndex = '100';
var spinnerElement = document.createElement("div");
spinnerElement.id = 'spinner';
spinnerElement.className = 'lv-determinate_circle lv-mid md';
spinnerElement.setAttribute("data-label", "Loading...");
spinnerElement.setAttribute("data-percentage", "true");
spinnerContainer.appendChild(spinnerElement);
container.appendChild(spinnerContainer);

var statsContainer = document.createElement("div");
statsContainer.id = 'statsContainer';
statsContainer.className = 'statsContainer';
statsContainer.style.position = 'relative';
statsContainer.style.right = '93%';
container.appendChild(statsContainer);

var guiContainer = document.createElement("div");
guiContainer.id = 'guiContainer';
guiContainer.className = 'guiContainer';
guiContainer.style.position = 'absolute';
guiContainer.style.right = '2%';
guiContainer.style.marginTop = '0px';
var guiElement = document.createElement("div");
guiElement.id = 'guiContainer';
guiElement.className = 'guiContainer';
guiElement.appendChild(guiContainer);
container.appendChild(guiContainer);

let spinner = new lv();
spinner.initLoaderAll();
spinner.startObserving();
let circle = lv.create(spinnerElement);

const raycaster = new THREE.Raycaster();
const pointer = new THREE.Vector2();
const onUpPosition = new THREE.Vector2();
const onDownPosition = new THREE.Vector2();

const geometry = new THREE.BoxGeometry( 20, 20, 20 );
let transformControl, transformControlLight, transformControlLightTarget;

const helperObjects = [];
const lightObjects = [];
var lightHelper, lightHelperTarget;

var selectedObject = false;
var selectedObjects = [];
var selectedFaces = [];
let pickingTexture;

var windowHalfX;
var windowHalfY;

var transformType = "";

var transformText =
{
    'Transform 3D Object': 'select type',
    'Transform Light': 'select type'
};

const colors = {
	DirectionalLight: '0xFFFFFF',
	AmbientLight: '0x404040'
};

const intensity = { startIntensityDir: 1 , startIntensityAmbient: 1};

const saveProperties = {
	Camera: true,
	Light: true
};

var EDITOR = false;

const gui = new GUI({ container: guiContainer });
//const mainHierarchyFolder = gui.addFolder('Hierarchy');
var hierarchyFolder;
const GUILength = 35;

let zoomImage = 1;
const ZOOM_SPEED_IMAGE = 0.1;

var canvasDimensions;
var compressedFile = '';
//guiContainer.appendChild(gui.domElement);

var options = {
    duration: 6500,
	gravity: "bottom",
	close: true,
    callback() {
        this.remove();
        Toastify.reposition();
    }
};
var myToast = Toastify(options);

const planeParams = {
	planeX: {
		constant: 0,
		negated: false,
		displayHelperX: false
	},
	planeY: {
		constant: 0,
		negated: false,
		displayHelperY: false
	},
	planeZ: {
		constant: 0,
		negated: false,
		displayHelperZ: false
	}
};

var clippingPlanes = [
		new THREE.Plane( new THREE.Vector3( - 1, 0, 0 ), 0 ),
		new THREE.Plane( new THREE.Vector3( 0, - 1, 0 ), 0 ),
		new THREE.Plane( new THREE.Vector3( 0, 0, - 1 ), 0 )
	];
var planeHelpers, clippingFolder;
var propertiesFolder;
var planeObjects = [];

var clippingGeometry = [];

var textMesh;

function readWissKI () {
	const xmlhttp = new XMLHttpRequest();
	xmlhttp.onload = function() {
		console.log(this.responseText);
	}
	xmlhttp.open("GET", "php/fetchWissKI.php?q=");
	xmlhttp.send();
}

//readWissKI();

function createClippingPlaneGroup( geometry, plane, renderOrder ) {

	const group = new THREE.Group();
	const baseMat = new THREE.MeshBasicMaterial();
	baseMat.depthWrite = false;
	baseMat.depthTest = false;
	baseMat.colorWrite = false;
	baseMat.stencilWrite = true;
	baseMat.stencilFunc = THREE.AlwaysStencilFunc;

	// back faces
	const mat0 = baseMat.clone();
	mat0.side = THREE.BackSide;
	mat0.clippingPlanes = [ plane ];
	mat0.stencilFail = THREE.IncrementWrapStencilOp;
	mat0.stencilZFail = THREE.IncrementWrapStencilOp;
	mat0.stencilZPass = THREE.IncrementWrapStencilOp;

	const mesh0 = new THREE.Mesh( geometry, mat0 );
	mesh0.renderOrder = renderOrder;
	group.add( mesh0 );

	// front faces
	const mat1 = baseMat.clone();
	mat1.side = THREE.FrontSide;
	mat1.clippingPlanes = [ plane ];
	mat1.stencilFail = THREE.DecrementWrapStencilOp;
	mat1.stencilZFail = THREE.DecrementWrapStencilOp;
	mat1.stencilZPass = THREE.DecrementWrapStencilOp;

	const mesh1 = new THREE.Mesh( geometry, mat1 );
	mesh1.renderOrder = renderOrder;

	group.add( mesh1 );

	return group;

}

function showToast (_str) {
	var myToast = Toastify(options);
	myToast.options.text = _str;
	myToast.showToast();
}

function addTextWatermark (_text, _scale) {
	var textGeo;
	var materials = [
		new THREE.MeshStandardMaterial( { color: 0xffffff, flatShading: true, side: THREE.DoubleSide, depthTest: false, depthWrite: false, transparent: true, opacity: 0.4 } ), // front
		new THREE.MeshStandardMaterial( { color: 0xffffff, flatShading: true, side: THREE.DoubleSide, depthTest: false, depthWrite: false, transparent: true, opacity: 0.4 } ) // side
	];
	const loader = new FontLoader();

	loader.load( '/modules/dfg_3dviewer/main/fonts/helvetiker_regular.typeface.json', function ( font ) {

		const textGeo = new TextGeometry( _text, {
			font: font,
			size: _scale*3,
			height: _scale/10,
			curveSegments: 5,
			bevelEnabled: true,
			bevelThickness: _scale/8,
			bevelSize: _scale/10,
			bevelOffset: 0,
			bevelSegments: 1
		} );
		textGeo.computeBoundingBox();

		const centerOffset = - 0.5 * ( textGeo.boundingBox.max.x - textGeo.boundingBox.min.x );

		textMesh = new THREE.Mesh( textGeo, materials );

		textMesh.rotation.z = Math.PI;
		textMesh.rotation.y = Math.PI;
		
		textMesh.position.x = 0;
		textMesh.position.y = 0;
		textMesh.position.z = 0;
		textMesh.renderOrder = 1;
		scene.add( textMesh );		
	} );
}

function selectObjectHierarchy (_id) {
	let search = true;
	for (let i = 0; i < selectedObjects.length && search === true; i++ ) {
		if (selectedObjects[i].id === _id) {
			search = false;
			if (selectedObjects[i].selected === true) {
				scene.getObjectById(_id).material = selectedObjects[i].originalMaterial;
				scene.getObjectById(_id).material.needsUpdate = true;
				selectedObjects[i].selected = false;
				selectedObjects.splice(selectedObjects.indexOf(selectedObjects[i]), 1);		
			}
		}
	}
	if (search) {
		selectedObjects.push({id: _id, selected: true, originalMaterial: scene.getObjectById(_id).material.clone()});
		const tempMaterial = scene.getObjectById(_id).material.clone();
		tempMaterial.color.setHex("0x00FF00");
		scene.getObjectById(_id).material = tempMaterial;
		scene.getObjectById(_id).material.needsUpdate = true;

	}
}

function fetchMetadata (_object, _type) {
	switch (_type) {
		case 'vertices':
			if (typeof (_object.geometry.index) !== "undefined" && _object.geometry.index !== null) {
				return _object.geometry.index.count;
			}
			else if (typeof (_object.attributes) !== "undefined" && _object.attributes !== null) {
				return _object.attributes.position.count;
			}
		break;
		case 'faces':
			if (typeof (_object.geometry.index) !== "undefined" && _object.geometry.index !== null) {
				return _object.geometry.index.count/3;
			}
			else if (typeof (_object.attributes) !== "undefined" && _object.attributes !== null) {
				return _object.attributes.position.count/3;
			}
		break;
	}
}

function setupObject (_object, _camera, _light, _data, _controls) {
	if (typeof (_data) !== "undefined") {
		_object.position.set (_data["objPosition"][0], _data["objPosition"][1], _data["objPosition"][2]);
		_object.scale.set (_data["objScale"][0], _data["objScale"][1], _data["objScale"][2]);
		_object.rotation.set (THREE.Math.degToRad(_data["objRotation"][0]), THREE.Math.degToRad(_data["objRotation"][1]), THREE.Math.degToRad(_data["objRotation"][2]));
		_object.needsUpdate = true;
		if (typeof (_object.geometry) !== "undefined") {
			_object.geometry.computeBoundingBox();
			_object.geometry.computeBoundingSphere();	
		}
	}
	else {
		var boundingBox = new THREE.Box3();
		if (Array.isArray(_object)) {
			for (var i = 0; i < _object.length; i++) {
				boundingBox.setFromObject( _object[i] );
				_object[i].position.set (0, 0, 0);
				_object[i].needsUpdate = true;
				if (typeof (_object[i].geometry) !== "undefined") {
					_object[i].geometry.computeBoundingBox();
					_object[i].geometry.computeBoundingSphere();	
				}			
			}
		}
		else {
			boundingBox.setFromObject( _object );
			_object.position.set (0, 0, 0);
			_object.needsUpdate = true;
			if (typeof (_object.geometry) !== "undefined") {
				_object.geometry.computeBoundingBox();
				_object.geometry.computeBoundingSphere();
			}
		}
	}
}

function setupClippingPlanes (_geometry, _size, _distance) {	
	clippingPlanes[ 0 ].constant = _distance.x;
	clippingPlanes[ 1 ].constant = _distance.y;
	clippingPlanes[ 2 ].constant = _distance.z;

	planeHelpers = clippingPlanes.map( (p) => new THREE.PlaneHelper( p, _size*2, 0xffffff ) );
	planeHelpers.forEach( (ph) => {
		ph.visible = false;
		ph.name = "PlaneHelper";
		scene.add( ph );
	} );
	distanceGeometry = _distance;
	clippingFolder.add( planeParams.planeX, 'displayHelperX' ).onChange( (v) => { planeHelpers[ 0 ].visible = v; renderer.localClippingEnabled = v; } );
	clippingFolder.add( planeParams.planeX, 'constant' ).min( - distanceGeometry.x ).max( distanceGeometry.x ).setValue(distanceGeometry.x).step(_size/100).listen().onChange(function (value) {
		clippingPlanes[ 0 ].constant = value;
		render();
	});


	clippingFolder.add( planeParams.planeY, 'displayHelperY' ).onChange( (v) => { planeHelpers[ 1 ].visible = v; renderer.localClippingEnabled = v; } );
	clippingFolder.add( planeParams.planeY, 'constant' ).min( - distanceGeometry.y ).max( distanceGeometry.y ).setValue(distanceGeometry.y).step(_size/100).listen().onChange(function (value) {
		clippingPlanes[ 1 ].constant = value;
		render();
	});


	clippingFolder.add( planeParams.planeZ, 'displayHelperZ' ).onChange( (v) => { planeHelpers[ 2 ].visible = v; renderer.localClippingEnabled = v; } );
	clippingFolder.add( planeParams.planeZ, 'constant' ).min( - distanceGeometry.z ).max( distanceGeometry.z ).setValue(distanceGeometry.z).step(_size/100).listen().onChange(function (value) {
		clippingPlanes[ 2 ].constant = value;
		render();
	});
}

function fitCameraToCenteredObject (camera, object, offset, orbitControls, _fit ) {
	const boundingBox = new THREE.Box3();
	if (Array.isArray(object)) {
		for (var i = 0; i < object.length; i++) {			
			boundingBox.setFromObject( object[i] );
		}
	}
	else {
		boundingBox.setFromObject( object );
	}

    var middle = new THREE.Vector3();
    var size = new THREE.Vector3();
    boundingBox.getSize(size);
	// ground
	var distance = new THREE.Vector3(Math.abs(boundingBox.max.x - boundingBox.min.x), Math.abs(boundingBox.max.y - boundingBox.min.y), Math.abs(boundingBox.max.z - boundingBox.min.z));
	gridSize = Math.max(distance.x, distance.y, distance.z);
	
	dirLightTarget = new THREE.Object3D();
	dirLightTarget.position.set(0,0,0);

	lightHelper = new THREE.DirectionalLightHelper( dirLight, gridSize );
	scene.add( lightHelper );
	lightHelper.visible = false;

	scene.add(dirLightTarget);
	dirLight.target = dirLightTarget;
	dirLight.target.updateMatrixWorld();
	

	var gridSizeScale = gridSize*1.5;
	const mesh = new THREE.Mesh( new THREE.PlaneGeometry( gridSizeScale, gridSizeScale ), new THREE.MeshPhongMaterial( { color: 0x999999, depthWrite: false, transparent: true, opacity: 0.85 } ) );
	mesh.rotation.x = - Math.PI / 2;
	mesh.position.set(0, 0, 0);
	mesh.receiveShadow = false;
	scene.add( mesh );	

	const axesHelper = new THREE.AxesHelper( gridSize );
	axesHelper.position.set(0, 0, 0);
	scene.add( axesHelper );
	
	const grid = new THREE.GridHelper( gridSizeScale, 80, 0x000000, 0x000000 );
	grid.material.opacity = 0.2;
	grid.material.transparent = true;
	grid.position.set(0, 0, 0);
	scene.add( grid );

    // figure out how to fit the box in the view:
    // 1. figure out horizontal FOV (on non-1.0 aspects)
    // 2. figure out distance from the object in X and Y planes
    // 3. select the max distance (to fit both sides in)
    //
    // The reason is as follows:
    //
    // Imagine a bounding box (BB) is centered at (0,0,0).
    // Camera has vertical FOV (camera.fov) and horizontal FOV
    // (camera.fov scaled by aspect, see fovh below)
    //
    // Therefore if you want to put the entire object into the field of view,
    // you have to compute the distance as: z/2 (half of Z size of the BB
    // protruding towards us) plus for both X and Y size of BB you have to
    // figure out the distance created by the appropriate FOV.
    //
    // The FOV is always a triangle:
    //
    //  (size/2)
    // +--------+
    // |       /
    // |      /
    // |     /
    // | F° /
    // |   /
    // |  /
    // | /
    // |/
    //
    // F° is half of respective FOV, so to compute the distance (the length
    // of the straight line) one has to: `size/2 / Math.tan(F)`.
    //
    // FTR, from https://threejs.org/docs/#api/en/cameras/PerspectiveCamera
    // the camera.fov is the vertical FOV.

    const fov = camera.fov * ( Math.PI / 180 );
    const fovh = 2*Math.atan(Math.tan(fov/2) * camera.aspect);
    let dx = size.z / 2 + Math.abs( size.x / 2 / Math.tan( fovh / 2 ) );
    let dy = size.z / 2 + Math.abs( size.y / 2 / Math.tan( fov / 2 ) );
    let cameraZ = Math.max(dx, dy);
	if (_fit) { cameraZ = camera.position.z; }

    // offset the camera, if desired (to avoid filling the whole canvas)
    if( offset !== undefined && offset !== 0 ) { cameraZ *= offset; }
	const coords = {x: camera.position.x, y: camera.position.y, z: cameraZ*0.8};
    new TWEEN.Tween(coords)
		.to({ z: cameraZ }, 800)
		.onUpdate(() =>
			{
				camera.position.set( coords.x, coords.y, coords.z );
				camera.updateProjectionMatrix();
				controls.update();
			}
      )
      .start();

    //camera.position.set( camera.position.x, camera.position.y, cameraZ );

    // set the far plane of the camera so that it easily encompasses the whole object
    const minZ = boundingBox.min.z;
    //const cameraToFarEdge = ( minZ < 0 ) ? -minZ + cameraZ : cameraZ - minZ;

    //camera.far = cameraToFarEdge * 3;
    camera.updateProjectionMatrix();

    if ( orbitControls !== undefined ) {
        // set camera to rotate around the center
        orbitControls.target = new THREE.Vector3(0, 0, 0);

        // prevent camera from zooming out far enough to create far plane cutoff
        //orbitControls.maxDistance = cameraToFarEdge * 2;
    }
	controls.update();
	
	setupClippingPlanes(object.geometry, gridSize, distance);
	
}

function buildGallery() {
	var fileElement = document.getElementsByClassName("field--type-file");
	fileElement[0].style.height = canvasDimensions.y*1.1 + "px";
	var mainElement = document.getElementById("block-bootstrap5-content");
	var imageElements = document.getElementsByClassName("field--type-image");
	var imageList = document.createElement("div");
	imageList.setAttribute('id', 'image-list');
	var modalGallery = document.createElement('div');
	var modalImage = document.createElement('img');
	modalImage.setAttribute('class', 'modalImage');
	modalGallery.addEventListener("wheel", function(e){
		e.preventDefault();
		e.stopPropagation();
		if(e.deltaY > 0 && zoomImage > 0.15) {    
			modalImage.style.transform = `scale(${zoomImage -= ZOOM_SPEED_IMAGE})`;  
		}
		else if (e.deltaY < 0 && zoomImage < 5) {    
			modalImage.style.transform = `scale(${zoomImage += ZOOM_SPEED_IMAGE})`;
		}
		return false;
	});
	var modalClose = document.createElement('span');
	modalGallery.setAttribute('id', 'modalGallery');
	modalGallery.setAttribute('class', 'modalGallery');
	modalClose.setAttribute('class', 'closeGallery');
	modalClose.setAttribute('title', 'Close');
	modalClose.innerHTML = "&times";
	modalClose.onclick = function() {
		modalGallery.style.display = "none";
	}

	document.addEventListener('click', function(event) {
		if (!modalGallery.contains(event.target) && !imageList.contains(event.target)) {
			modalGallery.style.display = "none";
			zoomImage = 1.0;
			modalImage.style.transform = `scale(1.0)`;
		}
	});

	modalGallery.appendChild(modalImage);
	modalGallery.appendChild(modalClose);
	for (var i = 0; imageElements.length - i; imageList.firstChild === imageElements[0] && i++) {
		imageElements[i].className += " image-list-item";
		imageElements[i].getElementsByTagName("a")[0].setAttribute("href", "#");
		imageElements[i].getElementsByTagName("img")[0].onclick = function(){
			modalGallery.style.display = "block";
			modalImage.src = this.src;
		};
		imageList.appendChild(imageElements[i]);
	}
	fileElement[0].insertAdjacentElement('beforebegin', modalGallery);
	mainElement.insertBefore(imageList, fileElement[0]);
}

function render() {
	controls.update();
	renderer.render( scene, camera );
}

function setupCamera (_object, _camera, _light, _data, _controls) {
	if (typeof (_data) != "undefined") {
		if (typeof (_data["cameraPosition"]) != "undefined") {
			_camera.position.set (_data["cameraPosition"][0], _data["cameraPosition"][1], _data["cameraPosition"][2]);
		}
		if (typeof (_data["controlsTarget"]) != "undefined") {
			_controls.target.set (_data["controlsTarget"][0], _data["controlsTarget"][1], _data["controlsTarget"][2]);
		}
		if (typeof (_data["lightPosition"]) != "undefined") {
			_light.position.set( _data["lightPosition"][0], _data["lightPosition"][1], _data["lightPosition"][2] );
		}
		if (typeof (_data["lightTarget"]) != "undefined") {
			_light.rotation.set( _data["lightTarget"][0], _data["lightTarget"][1], _data["lightTarget"][2] );
		}
		if (typeof (_data["lightColor"]) != "undefined") {
			_light.color = new THREE.Color( _data["lightColor"][0] );
		}
		if (typeof (_data["lightIntensity"]) != "undefined") {
			_light.intensity = _data["lightIntensity"][0];
		}
		if (typeof (_data["lightAmbientColor"]) != "undefined") {
			ambientLight.color = new THREE.Color( _data["lightAmbientColor"][0] );
		}
		if (typeof (_data["lightAmbientIntensity"]) != "undefined") {
			ambientLight.intensity = _data["lightAmbientIntensity"][0];
		}
		_camera.updateProjectionMatrix();
		_controls.update();
		fitCameraToCenteredObject ( _camera, _object, 2.3, _controls, true );
	}
	else {
		var boundingBox = new THREE.Box3();
		if (Array.isArray(_object)) {
			for (var i = 0; i < _object.length; i++) {
				boundingBox.setFromObject( _object[i] );
			}
		}
		else {
			boundingBox.setFromObject( _object );
		}
		var size = new THREE.Vector3();
		boundingBox.getSize(size);
		camera.position.set(size.x, size.y, size.z);
		fitCameraToCenteredObject ( _camera, _object, 2.3, _controls, false );
	}
}

function pickFaces(_id) {
	var sphere = new THREE.Mesh(new THREE.SphereGeometry(0.1, 7, 7), new THREE.MeshNormalMaterial({
				transparent : true,
				opacity : 0.8
			}));
	sphere.position.set(_id[0].point.x, _id[0].point.y, _id[0].point.z);
	scene.add(sphere);
	/*if (mainObject.name == "Scene" || mainObject.children.length > 0)
		mainObject.traverse( function ( child ) {
			if (child.isMesh) {
				child.traverse( function ( children ) {
				});
			}
		});
	else
		var intersects = raycaster.intersectObjects( mainObject, false );*/
}

function onWindowResize() {
	if (FULLSCREEN)
		canvasDimensions = {x: screen.width, y: screen.height};
	else
		canvasDimensions = {x: window.innerWidth*0.65, y: window.innerHeight*0.55};
	container.setAttribute("width", canvasDimensions.x);
	container.setAttribute("height", canvasDimensions.y);
	renderer.setPixelRatio( window.devicePixelRatio );
	camera.aspect = canvasDimensions.x / canvasDimensions.y;
	camera.updateProjectionMatrix();
	renderer.setSize( canvasDimensions.x, canvasDimensions.y );
	fullscreenMode.setAttribute('style', 'bottom:' + (-canvasDimensions.y*1.65 + 25) + 'px');
	controls.update();
	render();
}

function addWissKIMetadata(label, value) {
	if ((typeof (label) !== "undefined") && (typeof (value) !== "undefined")) {
		var _str = "";
		label = label.replace("wisski_path_3d_model__", "");
		switch (label) {
			case "title":
				_str = "Title";
			break;
			case "author_name":
				_str = "Author";
			break;
			/*case "reconstructed_period_start":
				_str = "period";
			break;
			case "reconstructed_period_end":
				_str = "-";
			break;*/
			case "author_affiliation":
				_str = "Author affiliation";
			break;
			case "license":
				_str = "License";
				switch (value) {
					case "CC0 1.0":
					case "CC-BY Attribution":
					case "CC-BY-SA Attribution-ShareAlike":
					case "CC-BY-ND Attribution-NoDerivs":
					case "CC-BY-NC Attribution-NonCommercial":
					case "CC-BY-NC-SA Attribution-NonCommercial-ShareAlike":
					case "CC BY-NC-ND Attribution-NonCommercial-NoDerivs":
						//addTextWatermark("©", gridSize/10);
					break;
				}
			break;
			default:
				_str = ""
			break;
		}
		if (_str == "period") {
			return "Reconstruction period: <b>"+value+" - ";
		}
		else if (_str == "-") {
			return value+"</b><br>";
		}
		else if (_str !== "") {
			return _str+": <b>"+value+"</b><br>";
		}
	}
}

function truncateString(str, n) {
	if (str.length === 0) {return str;}
	else if (str.length > n) {
		return str.substring(0, n) + "...";
	} else {
		return str;
	}
}

function getProxyPath(url) {
	var tempPath = decodeURIComponent(proxyPath);
	return tempPath.replace(originalPath, encodeURIComponent(url));
}

function expandMetadata () {
   const el = document.getElementById("metadata-content");
   el.classList.toggle('expanded');
   const elm = document.getElementById("metadata-collapse");
   elm.classList.toggle('metadata-collapsed');
}

function fullscreen() {
	FULLSCREEN=!FULLSCREEN;
	var _container = document.getElementById("MainCanvas");
	if (_container.requestFullscreen && FULLSCREEN) {
		_container.requestFullscreen();
	} 
	else if (_container.webkitRequestFullscreen && FULLSCREEN) { /* Safari */
		_container.webkitRequestFullscreen();
	}
	else if (_container.msRequestFullscreen && FULLSCREEN) { /* IE11 */
		_container.msRequestFullscreen();
	}
	else if (_container.mozRequestFullScreen && FULLSCREEN) { /* IE11 */
		_container.mozRequestFullScreen();
	}
	else {
		document.exitFullscreen();
		FULLSCREEN=false;
	}
	onWindowResize();
}

function fetchSettings ( path, basename, filename, object, camera, light, controls, orgExtension, extension ) {
	var metadata = {'vertices': 0, 'faces': 0};
	var hierarchy = [];
	var geometry;
	var metadataUrl = path + "metadata/" + filename + "_viewer";
	if (proxyPath) {
		metadataUrl = getProxyPath(metadataUrl);
	}
	fetch(metadataUrl, {cache: "no-cache"})
	.then((response) => {
		if (response['status'] !== 404) {
			showToast("Settings " + filename + "_viewer found");
			return response.json();
		}
		else if (response['status'] === 404) {
			showToast("No settings " + filename + "_viewer found");
		}
		})
	.then(data => {
		var tempArray = [];
		const hierarchyMain = gui.addFolder( 'Hierarchy' ).close();
		if (object.name === "Scene" || object.children.length > 0 ) {
			setupObject(object, camera, light, data, controls);
			object.traverse( function ( child ) {
				if ( child.isMesh ) {
					metadata['vertices'] += fetchMetadata (child, 'vertices');
					metadata['faces'] += fetchMetadata (child, 'faces');
					var shortChildName = truncateString(child.name, GUILength);
					if (child.name === '') {
						tempArray = {["Mesh"]() {selectObjectHierarchy(child.id)}, 'id': child.id};
					}
					else {
						tempArray = { [shortChildName]() {selectObjectHierarchy(child.id)}, 'id': child.id};
					}
					hierarchyFolder = hierarchyMain.addFolder(shortChildName).close();
					hierarchyFolder.add(tempArray, shortChildName);
					clippingGeometry.push(child.geometry);
					child.traverse( function ( children ) {
						if ( children.isMesh &&  children.name !== child.name) {
							var shortChildrenName = truncateString(children.name, GUILength);
							if (children.name === '') {
								tempArray = {["Mesh"] (){selectObjectHierarchy(children.id)}, 'id': children.id};
							}
							else {
								tempArray = { [shortChildrenName] (){selectObjectHierarchy(children.id)}, 'id': children.id};
							}
							clippingGeometry.push(children.geometry);
							hierarchyFolder.add(tempArray, shortChildrenName);
						}
					});
				}
			});
			setupCamera (object, camera, light, data, controls);				
		}
		else {
			setupObject(object, camera, light, data, controls);
			setupCamera (object, camera, light, data, controls);
			metadata['vertices'] += fetchMetadata (object, 'vertices');
			metadata['faces'] += fetchMetadata (object, 'faces');
			if (object.name === '') {
				tempArray = {["Mesh"] (){selectObjectHierarchy(object.id)}, 'id': object.id};
				object.name = object.id;
			}
			else {
				tempArray = {[object.name] (){selectObjectHierarchy(object.id)}, 'id': object.id};
			}
			//hierarchy.push(tempArray);
			clippingGeometry.push(object.geometry);
			hierarchyFolder = hierarchyMain.addFolder(object.name).close();
			hierarchyFolder.add(tempArray, 'name' ).name(object.name);
			metadata['vertices'] += fetchMetadata (object, 'vertices');
			metadata['faces'] += fetchMetadata (object, 'faces');
		}

		hierarchyMain.domElement.classList.add("hierarchy");
		
		var metadataContainer = document.createElement('div');
		metadataContainer.setAttribute('id', 'metadata-container');
		var metadataContent = '<div id="metadata-collapse" class="metadata-collapse">METADATA </div><div id="metadata-content" class="metadata-content">';
		metadataContentTech = '<hr class="metadataSeparator">';
		metadataContentTech += 'Uploaded file name: <b>' + basename + "." + orgExtension + '</b><br>';
		metadataContentTech += 'Loaded format: <b>' + extension + '</b><br>';
		metadataContentTech += 'Vertices: <b>' + metadata['vertices'] + '</b><br>';
		metadataContentTech += 'Faces: <b>' + metadata['faces'] + '</b><br>';

		var xmlPath = wisskiUrl;
		if (proxyPath) {
			xmlPath = getProxyPath(xmlPath);
		}
		var req = new XMLHttpRequest();
		req.responseType = 'xml';
		req.open('GET', xmlPath, true);
		req.onreadystatechange = function (aEvt) {
			if (req.readyState == 4) {
				if(req.status == 200) {
					const parser = new DOMParser();
					const doc = parser.parseFromString(req.responseText, "application/xml");
					var data = doc.documentElement.childNodes[0].childNodes;
					if (typeof (data) !== undefined) {
						for(var i = 0; i < data.length; i++) {
							var fetchedValue = addWissKIMetadata(data[i].tagName, data[i].textContent);
							if (typeof(fetchedValue) !== "undefined") {
								metadataContent += fetchedValue;
							}
						}
					}
					metadataContent += metadataContentTech + '</div>';
					canvasText.innerHTML = metadataContent;
					metadataContainer.appendChild( canvasText );
					var downloadModel = document.createElement('div');
					downloadModel.setAttribute('id', 'downloadModel');
					var viewEntity = document.createElement('div');
					viewEntity.setAttribute('id', 'viewEntity');
					var c_path = path;
					if (compressedFile !== '') { c_path = domain + '/' +uri; }
					console.log(domain + uri);
					downloadModel.innerHTML = "<a href='" + c_path + filename + "' download><img src='/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/img/cloud-arrow-down.svg' alt='download' width=25 height=25 title='Download source file'/></a>";
					viewEntity.innerHTML = "<a href='" + domain + "/wisski/navigate/" + wisskiID + "/view' target='_blank'><img src='/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/img/share.svg' alt='View Entity' width=22 height=22 title='View Entity'/></a>";
					metadataContainer.appendChild( downloadModel );
					metadataContainer.appendChild( viewEntity );
					var fullscreenMode = document.createElement('div');
					fullscreenMode.setAttribute('id', 'fullscreenMode');
					fullscreenMode.setAttribute('style', 'bottom:' + Math.round(-canvasDimensions.y * 1.05 + 26) + 'px');
					fullscreenMode.innerHTML = "<img src='/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/img/fullscreen.png' alt='Fullscreen' width=20 height=20 title='Fullscreen mode'/>";
					metadataContainer.appendChild(fullscreenMode);
					//var _container = document.getElementById("MainCanvas");
					container.appendChild(metadataContainer);
					document.getElementById ("metadata-collapse").addEventListener ("click", expandMetadata, false);
					document.getElementById ("fullscreenMode").addEventListener ("click", fullscreen, false);
				}
				else
					console.log("Error during loading metadata content\n");
				}
		};
		req.send(null);
		//hierarchyFolder.add(hierarchyText, 'Faces' );
	});
	helperObjects.push (object);
	if (proxyPath) {
		circle.set(100, 100);
		circle.hide();
		showToast("Model has been loaded.");
		EXIT_CODE=0;
	}
	//addTextWatermark("©", object.scale.x);
	//lightObjects.push (object);
}

const onError = function () {
	//circle.set(100, 100);
	circle.hide();
	EXIT_CODE=1;
};

const onProgress = function ( xhr ) {
	var percentComplete = xhr.loaded / xhr.total * 100;
	circle.show();
	circle.set(percentComplete, 100);
	if (percentComplete >= 100) {
		circle.hide();
		showToast("Model has been loaded.");
		EXIT_CODE=0;
	}
};

function loadModel ( path, basename, filename, extension, orgExtension ) {
	if (!imported) {
		circle.show();
		circle.set(0, 100);
		var modelPath = path + filename;
		if (proxyPath) {
			modelPath = getProxyPath(modelPath);
		}
		switch(extension) {
			case 'obj':
			case 'OBJ':
				const manager = new THREE.LoadingManager();
				manager.onLoad = function ( ) { showToast ("OBJ model has been loaded"); };
				manager.addHandler( /\.dds$/i, new DDSLoader() );
				// manager.addHandler( /\.tga$/i, new TGALoader() );
				new MTLLoader( manager )
					.setPath( path )
					.load( basename + '.mtl', function ( materials ) {
						materials.preload();
						new OBJLoader( manager )
							.setMaterials( materials )
							.setPath( path )
							.load( filename, function ( object ) {
								object.position.set (0, 0, 0);
								scene.add( object );
								fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
								mainObject.push(object);
							}, onProgress, onError );
					} );
			break;
			
			case 'fbx':
			case 'FBX':
				var FBXloader = new FBXLoader();
				FBXloader.load( modelPath, function ( object ) {
					object.traverse( function ( child ) {
						if ( child.isMesh ) {
							child.castShadow = true;
							child.receiveShadow = true;
						}
					} );
					object.position.set (0, 0, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object.children, camera, controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'ply':
			case 'PLY':
				loader = new PLYLoader();
				loader.load( modelPath, function ( geometry ) {
					geometry.computeVertexNormals();
					const material = new THREE.MeshStandardMaterial( { color: 0x0055ff, flatShading: true } );
					const object = new THREE.Mesh( geometry, material );
					object.position.set (0, 0, 0);
					object.castShadow = true;
					object.receiveShadow = true;
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'dae':
			case 'DAE':
				const loadingManager = new THREE.LoadingManager( function () {
					scene.add( object );
				} );
				loader = new ColladaLoader( loadingManager );
				loader.load( modelPath, function ( object ) {
					object = object.scene;
					object.position.set (0, 0, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'ifc':
			case 'IFC':
				const ifcLoader = new IFCLoader();
				ifcLoader.ifcManager.setWasmPath( '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/js/jsm/loaders/ifc/' );
				ifcLoader.load( modelPath, function ( object ) {
					//object.position.set (0, 300, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'stl':
			case 'STL':
				loader = new STLLoader();
				loader.load( modelPath, function ( geometry ) {
					let meshMaterial = new THREE.MeshPhongMaterial( { color: 0xff5533, specular: 0x111111, shininess: 200 } );
					if ( geometry.hasColors ) {
						meshMaterial = new THREE.MeshPhongMaterial( { opacity: geometry.alpha, vertexColors: true } );
					}
					const object = new THREE.Mesh( geometry, meshMaterial );
					object.position.set (0, 0, 0);
					object.castShadow = true;
					object.receiveShadow = true;
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;

			case 'xyz':
			case 'XYZ':
				loader = new XYZLoader();
				loader.load( modelPath, function ( geometry ) {
					geometry.center();
					const hasVertexColors = ( geometry.hasAttribute( 'color' ) === true );
					const material = new THREE.PointsMaterial( { size: 0.1, vertexColors: hasVertexColors } );
					object = new THREE.Points( geometry, material );
					object.position.set (0, 0, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;

			case 'pcd':
			case 'PCD':
				loader = new PCDLoader();
				loader.load( modelPath, function ( mesh ) {
					scene.add( mesh );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;

			case 'json':
			case 'JSON':
				loader = new THREE.ObjectLoader();
				loader.load( modelPath, function ( object ) {
						object.position.set (0, 0, 0);
						scene.add( object );
						fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
						mainObject.push(object);
					}, onProgress, onError );
			break;

			case '3ds':
			case '3DS':
				loader = new TDSLoader( );
				loader.setResourcePath( path );
				modelPath = path + basename + "." + extension;
				if (proxyPath) {
					modelPath = getProxyPath(modelPath);
				}
				loader.load( modelPath, function ( object ) {
					object.traverse( function ( child ) {
						if ( child.isMesh ) {
							//child.material.specular.setScalar( 0.1 );
							//child.material.normalMap = normal;
						}
					} );
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;

			case 'zip':
			case 'ZIP':
			case 'rar':
			case 'RAR':
			case 'tar':
			case 'TAR':
			case 'gz':
			case 'GZ':
			case 'xz':
			case 'XZ':
				showToast("Model is being loaded from compressed archive.");
			break;
			
			case 'glb':
			case 'GLB':
			case 'gltf':
			case 'GLTF':
				const dracoLoader = new DRACOLoader();
				dracoLoader.setDecoderPath( '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/js/libs/draco/' );
				dracoLoader.preload();
				const gltf = new GLTFLoader();
				gltf.setDRACOLoader(dracoLoader);
				showToast("Trying to load model from " + extension + " representation.");
				modelPath = path + basename + "." + extension;
				if (proxyPath) {
					modelPath = getProxyPath(modelPath);
				}
				gltf.load(modelPath, function(gltf) {
					gltf.scene.traverse( function ( child ) {
						if ( child.isMesh ) {
							child.castShadow = true;
							child.receiveShadow = true;
							child.geometry.computeVertexNormals();
							if(child.material.map) { child.material.map.anisotropy = 16 };
							child.material.side = THREE.DoubleSide;
							child.material.clippingPlanes = clippingPlanes;
							child.material.clipIntersection = false;
							mainObject.push(child);	
						}
					});
					fetchSettings (path.replace("gltf/", ""), basename, filename, gltf.scene, camera, lightObjects[0], controls, orgExtension, extension );
					scene.add( gltf.scene );
				},
					function ( xhr ) {
						var percentComplete = xhr.loaded / xhr.total * 100;
						if (percentComplete !== Infinity) {
							circle.set(percentComplete, 100);
							if (percentComplete >= 100) {
								circle.hide();
								showToast("Model " + filename + " has been loaded.");
							}
						}
					},
					function ( ) {						
							showToast("GLTF representation not found, trying original file " + path.replace("gltf/", "") + filename + " [" + orgExtension + "]");
							allowedFormats.forEach(function(item, index, array) {
								if (EXIT_CODE != 0) { loadModel(path.replace("gltf/", ""), basename, filename, item, orgExtension); }
							});
							//loadModel(path.replace("gltf/", ""), basename, filename, orgExtension, orgExtension);
							imported = true;
					}
				);
			break;
			default:
				showToast("Extension not supported yet");
		}
	}
	else {
		showToast("File " + path + basename + " not found.");
		//circle.set(100, 100);
		circle.hide();
	}
	
	scene.updateMatrixWorld();
}

//

function animate() {
	requestAnimationFrame( animate );
	const delta = clock.getDelta();
	if ( mixer ) mixer.update( delta ); {
		TWEEN.update();
	}
	for ( let i = 0; i < clippingPlanes.length && clippingMode; i ++ ) {
		const plane = clippingPlanes[ i ];
		const po = planeObjects[ i ];
		if (po !== undefined ) {
			plane.coplanarPoint( po.position );
			po.lookAt(
				po.position.x - plane.normal.x,
				po.position.y - plane.normal.y,
				po.position.z - plane.normal.z,
			);
		}
	}
	if (textMesh !== undefined) { textMesh.lookAt(camera.position); }
	renderer.render( scene, camera );
	stats.update();
}

function updateObject () {
}

function onPointerDown( e ) {
	//onDownPosition.x = event.clientX;
	//onDownPosition.y = event.clientY;
	onDownPosition.x = ( e.clientX / canvasDimensions.x ) * 2 - 1;
	onDownPosition.y = - ( e.clientY / canvasDimensions.y ) * 2 + 1;
}

function onPointerUp( e ) {
    onUpPosition.x = ( e.clientX / canvasDimensions.x ) * 2 - 1;
    onUpPosition.y = -( e.clientY / canvasDimensions.y ) * 2 + 1;
	var mouseVector = new THREE.Vector2();
	//onUpPosition.x = ((e.clientX - container.offsetLeft) / canvasDimensions.x) * 2 - 1;
	//onUpPosition.y =  - ((e.clientY - (container.offsetTop - document.body.scrollTop + 11)) / (canvasDimensions.y)) * 2 + 1;
	raycaster.setFromCamera( pointer, camera );
	var intersects;
	if (EDITOR) {
		if (mainObject.name === "Scene" || mainObject.length > 1) {
			/*for (let ii = 0; ii < mainObject.length; ii++) {	
				intersects = raycaster.intersectObjects( mainObject[ii].children, false );
			}*/
			intersects = raycaster.intersectObjects( mainObject[0].children, false );
		}
		else {
			intersects = raycaster.intersectObjects( mainObject[0], false );
		}
		if (intersects.length > 0) {pickFaces(intersects); }
	}
}

function onPointerMove( event ) {
	//pointer.x = (event.clientX / renderer.domElement.clientWidth) * 2 - 1;
	//pointer.y = -(event.clientY / renderer.domElement.clientHeight) * 2 + 1;
	pointer.x = ( ( event.clientX - container.getBoundingClientRect().left ) / (canvasDimensions.x - 200 )) * 2 - 1;
	pointer.y = - ((event.clientY - (container.getBoundingClientRect().top - document.body.scrollTop - 50)) / (canvasDimensions.y)) * 2 + 1;
	/*pointer.x = ( event.clientX - windowHalfX ) / canvasDimensions.x;
	pointer.y = ( event.clientY - windowHalfY ) / canvasDimensions.y;
	raycaster.setFromCamera( pointer, camera );
	if (typeof(helperObjects[0]) !== "undefined") {
		if (helperObjects[0].name == "Scene" || helperObjects[0].children.length > 0)
			var intersects = raycaster.intersectObjects( helperObjects[0].children, false );
		else
			var intersects = raycaster.intersectObjects( helperObjects[0], false );
		if ( intersects.length > 0 ) {
			const object = intersects[ 0 ].object;
			if ( object !== transformControl.object ) {
				if ( transformType != "" ) {
					transformControl.mode = transformType;
					transformControl.attach( helperObjects[0] );
				}
			}
		}
	}*/
}

function changeScale () {
	if (transformControl.getMode() === "scale") {
		switch (transformControl.axis) {
			case 'X':
			case 'XY':
				helperObjects[0].scale.set(helperObjects[0].scale.x,helperObjects[0].scale.x,helperObjects[0].scale.x);
			break;
			case 'Y':
			case 'YZ':
				helperObjects[0].scale.set(helperObjects[0].scale.y,helperObjects[0].scale.y,helperObjects[0].scale.y);
			break;
			case 'Z':
			case 'XZ':
				helperObjects[0].scale.set(helperObjects[0].scale.x,helperObjects[0].scale.x,helperObjects[0].scale.x);
			break;
		}
	}
}

function calculateObjectScale () {
	const boundingBox = new THREE.Box3();
	if (Array.isArray(helperObjects[0])) {
		for (var i = 0; i < helperObjects[0].length; i++) {			
			boundingBox.setFromObject( object[i] );
		}
	}
	else {
		boundingBox.setFromObject( helperObjects[0] );
	}

    var middle = new THREE.Vector3();
    var size = new THREE.Vector3();
    boundingBox.getSize(size);
	// ground
	var _distance = new THREE.Vector3(Math.abs(boundingBox.max.x - boundingBox.min.x), Math.abs(boundingBox.max.y - boundingBox.min.y), Math.abs(boundingBox.max.z - boundingBox.min.z));
	distanceGeometry = _distance;
	planeParams.planeX.constant = clippingFolder.controllers[1]._max = clippingPlanes[ 0 ].constant = _distance.x;
	clippingFolder.controllers[1]._min = -clippingFolder.controllers[1]._max;
	planeParams.planeY.constant = clippingFolder.controllers[3]._max = clippingPlanes[ 1 ].constant = _distance.y;
	clippingFolder.controllers[3]._min = -clippingFolder.controllers[3]._max;
	planeParams.planeZ.constant = clippingFolder.controllers[5]._max = clippingPlanes[ 2 ].constant = _distance.z;
	clippingFolder.controllers[5]._min = -clippingFolder.controllers[5]._max;
	clippingFolder.controllers[1].updateDisplay();
	clippingFolder.controllers[3].updateDisplay();
	clippingFolder.controllers[5].updateDisplay();
	var _maxDistance = Math.max(_distance.x, _distance.y, _distance.z);
	planeHelpers[0].size = planeHelpers[1].size = planeHelpers[2].size = _maxDistance;
}

function changeLightRotation () {
	lightHelper.update();
}

function init() {
	// model
	//canvasDimensions = {x: container.getBoundingClientRect().width, y: container.getBoundingClientRect().bottom};
	canvasDimensions = {x: window.self.innerWidth*0.65, y: window.self.innerHeight*0.55};
	container.setAttribute("width", canvasDimensions.x);
	container.setAttribute("height", canvasDimensions.y);

	camera = new THREE.PerspectiveCamera( 45, canvasDimensions.x / canvasDimensions.y, 0.1, 999000000 );
	camera.position.set( 0, 0, 0 );

	scene = new THREE.Scene();
	scene.background = new THREE.Color( 0xa0a0a0 );
	//scene.fog = new THREE.Fog( 0xa0a0a0, 90000, 1000000 );

	const hemiLight = new THREE.HemisphereLight( 0xffffff, 0x444444 );
	hemiLight.position.set( 0, 200, 0 );
	scene.add( hemiLight );
	
	ambientLight = new THREE.AmbientLight( 0x404040 ); // soft white light
	scene.add( ambientLight );

	dirLight = new THREE.DirectionalLight( 0xffffff );
	dirLight.position.set( 0, 100, 50 );
	dirLight.castShadow = true;
	dirLight.shadow.camera.top = 180;
	dirLight.shadow.camera.bottom = - 100;
	dirLight.shadow.camera.left = - 120;
	dirLight.shadow.camera.right = 120;
	dirLight.shadow.bias = -0.0001;
	dirLight.shadow.mapSize.width = 1024*4;
	dirLight.shadow.mapSize.height = 1024*4;
	scene.add( dirLight );
	lightObjects.push( dirLight );

	renderer = new THREE.WebGLRenderer( { antialias: true, logarithmicDepthBuffer: true, colorManagement: true, sortObjects: true, preserveDrawingBuffer: true, powerPreference: "high-performance" } );
	renderer.setPixelRatio( window.devicePixelRatio );
	renderer.setSize( canvasDimensions.x, canvasDimensions.y );
	renderer.shadowMap.enabled = true;
	renderer.localClippingEnabled = false;
	renderer.setClearColor( 0x263238 );
	renderer.domElement.id = 'MainCanvas';
	container.appendChild( renderer.domElement );
	
	canvasText = document.createElement('div');
	canvasText.id = "TextCanvas";
	canvasText.width = canvasDimensions.x;
	canvasText.height = canvasDimensions.y;

	//DRUPAL WissKI [start]
	//buildGallery();
	//DRUPAL WissKI [end]

	controls = new OrbitControls( camera, renderer.domElement );
	controls.target.set( 0, 100, 0 );
	controls.update();
	
	transformControl = new TransformControls( camera, renderer.domElement );
	transformControl.rotationSnap = THREE.Math.degToRad(5);
	transformControl.space = "local";
	transformControl.addEventListener( 'change', render );
	transformControl.addEventListener( 'objectChange', changeScale );
	transformControl.addEventListener( 'mouseUp', calculateObjectScale );
	transformControl.addEventListener( 'dragging-changed', function ( event ) {
		controls.enabled = ! event.value
	} );
	scene.add( transformControl );
	
	transformControlLight = new TransformControls( camera, renderer.domElement );
	transformControlLight.space = "local";
	transformControlLight.addEventListener( 'change', render );
	//transformControlLight.addEventListener( 'objectChange', changeLightRotation );
	transformControlLight.addEventListener( 'dragging-changed', function ( event ) {
		controls.enabled = ! event.value;
	} );
	scene.add( transformControlLight );

	transformControlLightTarget = new TransformControls( camera, renderer.domElement );
	transformControlLightTarget.space = "global";
	transformControlLightTarget.addEventListener( 'change', render );
	transformControlLightTarget.addEventListener( 'objectChange', changeLightRotation );
	transformControlLightTarget.addEventListener( 'dragging-changed', function ( event ) {
		controls.enabled = ! event.value;
	} );
	scene.add( transformControlLightTarget );

	/*try {
	} catch (e) {
		// statements to handle any exceptions
		loadModel(path, basename, filename, extension);
	}*/
	if (extension === "glb" || extension === "GLB" || extension === "gltf" || extension === "GLTF") {
		loadModel (path, basename, filename, extension, extension);
	}
	else if  (extension === "zip" || extension === "ZIP" ) {
		compressedFile = "_ZIP/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else if  (extension === "rar" || extension === "RAR" ) {
		compressedFile = "_RAR/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else if  (extension === "tar" ) {
		compressedFile = "_TAR/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else if  (extension === "xz" ) {
		compressedFile = "_XZ/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else if  (extension === "gz" ) {
		compressedFile = "_GZ/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else {
		loadModel (path+"gltf/", basename, filename, "glb", extension);
	}

	container.addEventListener( 'pointerdown', onPointerDown );
	container.addEventListener( 'pointerup', onPointerUp );
	container.addEventListener( 'pointermove', onPointerMove );
	window.addEventListener( 'resize', onWindowResize );

	// stats
	stats = new Stats();
	stats.domElement.style.cssText = 'position:relative;top:0px;left:0px;max-height:120px;max-width:90px;z-index:2;';
	container.appendChild( stats.dom );
	
	windowHalfX = canvasDimensions.x / 2;
	windowHalfY = canvasDimensions.y / 2;
	
	const editorFolder = gui.addFolder('Editor').close();
	editorFolder.add(transformText, 'Transform 3D Object', { None: '', Move: 'translate', Rotate: 'rotate', Scale: 'scale' } ).onChange(function (value)
	{ 
		if (value === '') { transformControl.detach(); } 
		else { 
			transformControl.mode = value; 
			transformControl.attach( helperObjects[0] );
		}
	});
	const lightFolder = editorFolder.addFolder('Directional Light').close();
	lightFolder.add(transformText, 'Transform Light', { None: '', Move: 'translate', Target: 'rotate' } ).onChange(function (value)
	{ 
		if (value === '') { transformControlLight.detach(); transformControlLightTarget.detach(); lightHelper.visible = false; } else {
			if (value === "translate") {
				transformControlLight.mode = "translate";
				transformControlLight.attach( dirLight );
				lightHelper.visible = true;
				transformControlLightTarget.detach();
			}
			else {
				transformControlLightTarget.mode = "translate";
				transformControlLightTarget.attach( dirLightTarget );
				lightHelper.visible = true;
				transformControlLight.detach();
			}
		}
	});
	lightFolder.addColor ( colors, 'DirectionalLight' ).onChange(function (value) {
		const tempColor = new THREE.Color( value );
		lightObjects[0].color = tempColor ;
	});
	lightFolder.add( intensity, 'startIntensityDir', 0, 10 ).onChange(function (value) {
		lightObjects[0].intensity = value;
	});

	const lightFolderAmbient = editorFolder.addFolder('Ambient Light').close();
	lightFolderAmbient.addColor ( colors, 'AmbientLight' ).onChange(function (value) {
		const tempColor = new THREE.Color( value );
		ambientLight.color = tempColor ;
	});
	lightFolderAmbient.add( intensity, 'startIntensityAmbient', 0, 10 ).onChange(function (value) {
		ambientLight.intensity = value;
	});

	propertiesFolder = editorFolder.addFolder('Save properties').close();
	propertiesFolder.add( saveProperties, 'Camera' ); 
	propertiesFolder.add( saveProperties, 'Light' ); 

	if (editor) {
		editorFolder.add({["Save"] (){
			var xhr = new XMLHttpRequest(),
				jsonArr,
				method = "POST",
				jsonRequestURL = domain + "/editor.php";

			xhr.open(method, jsonRequestURL, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			var params;
			var rotateMetadata = new THREE.Vector3(THREE.Math.radToDeg(helperObjects[0].rotation.x),THREE.Math.radToDeg(helperObjects[0].rotation.y),THREE.Math.radToDeg(helperObjects[0].rotation.z));
			var newMetadata = ({"objPosition": [ helperObjects[0].position.x, helperObjects[0].position.y, helperObjects[0].position.z ], "objScale": [ helperObjects[0].scale.x, helperObjects[0].scale.y, helperObjects[0].scale.z ], "objRotation": [ rotateMetadata.x, rotateMetadata.y, rotateMetadata.z ] });
			if (saveProperties.Camera) { newMetadata = Object.assign(newMetadata, {"cameraPosition": [ camera.position.x, camera.position.y, camera.position.z ], "controlsTarget": [ controls.target.x, controls.target.y, controls.target.z ]}); }
			if (saveProperties.Light) { newMetadata = Object.assign(newMetadata, {"lightPosition": [ dirLight.position.x, dirLight.position.y, dirLight.position.z ], "lightTarget": [ dirLight.rotation._x, dirLight.rotation._y, dirLight.rotation._z ], "lightColor": [ "#" + (dirLight.color.getHexString()).toUpperCase() ], "lightIntensity": [ dirLight.intensity ], "lightAmbientColor": [ "#" + (ambientLight.color.getHexString()).toUpperCase() ], "lightAmbientIntensity": [ ambientLight.intensity ] }); }
			if (compressedFile !== '') { params = "5MJQTqB7W4uwBPUe="+JSON.stringify(newMetadata, null, '\t')+"&path="+uri+basename+compressedFile+"&filename="+filename; }
			else { params = "5MJQTqB7W4uwBPUe="+JSON.stringify(newMetadata, null, '\t')+"&path="+uri+"&filename="+filename; }
			xhr.onreadystatechange = function()
			{
				if(xhr.readyState === XMLHttpRequest.DONE) {
					var status = xhr.status;
					if (status === 0 || (status >= 200 && status < 400)) {
						showToast ("Settings have been saved.");
					}
				}
			};
			xhr.send(params);
		}}, 'Save');
		/*editorFolder.add({["Picking"] (){
			EDITOR=!EDITOR;
			var _str;
			EDITOR ? _str = "enabled" : _str = "disabled";
			showToast ("Face picking is " + _str);
		}}, 'Picking');*/
		clippingFolder = editorFolder.addFolder('Clipping Planes').close();
	}
}

window.onload = function() {
	init();
	animate();
};
