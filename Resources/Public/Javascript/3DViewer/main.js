/*
DFG 3D-Viewer
Copyright (C) 2022 - Daniel Dworak

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details at 
https://www.gnu.org/licenses/.
*/

//Supported file formats: OBJ, DAE, FBX, PLY, IFC, STL, XYZ, JSON, 3DS, PCD, glTF


import * as THREE from './build/three.module.js';
import { TWEEN } from './js/jsm/libs/tween.module.min.js';

import Stats from './js/jsm/libs/stats.module.js';

import { OrbitControls } from './js/jsm/controls/OrbitControls.js';
import { TransformControls } from './js/jsm/controls/TransformControls.js';
// BEGIN - path can't be changed while updating
import { GUI } from './node_modules/lil-gui/dist/lil-gui.esm.min.js';
// END - path can't be changed while updating
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
import { PCDLoader } from './js/jsm/loaders/PCDLoader.js';
import { FontLoader } from './js/jsm/loaders/FontLoader.js';
import { TextGeometry } from './js/jsm/geometries/TextGeometry.js';

//import CONFIG from './config.json' assert {type: 'json'}; //disabled temporary because of Firefox assertion bug
const CONFIG = {
	"domain": "https://3d-repository.hs-mainz.de",
	"metadataDomain": "https://3d-repository.hs-mainz.de",
	"container": "DFG_3DViewer",
	"galleryContainer": "block-bootstrap5-content",
	"galleryImageClass": "field--type-image"
};

let camera, scene, renderer, stats, controls, loader, ambientLight, dirLight, dirLightTarget, cameraLight, cameraLightTarget;
let imported;
var mainObject = [];
var metadataContentTech;
var mainCanvas;
var distanceGeometry = new THREE.Vector3();
let wisskiID = '';

const clock = new THREE.Clock();
const editor = true;
var FULLSCREEN = false;

let mixer;

const container = document.getElementById(CONFIG.container);
container.setAttribute("width", window.self.innerWidth);
container.setAttribute("height", window.self.innerHeight);
container.setAttribute("display", "flex");
// BEGIN - part necessary to keep while updating
const model = container.getAttribute("model");
const xmlPath = container.getAttribute("xml");
const settingsPath= container.getAttribute("settings");
const proxy = container.getAttribute("proxy");
const dfgViewer = true;
var elementsURL;
if (dfgViewer) {
	elementsURL = decodeURIComponent(xmlPath).match("/export_xml_single/(.*)?page");
	if (elementsURL) {
		wisskiID = parseInt(elementsURL[1]);
	}
} else {
// END - part necessary to keep while updating
	elementsURL = window.location.pathname;
	elementsURL = elementsURL.match("/wisski/navigate/(.*)/view");
	wisskiID = elementsURL[1];
	container.setAttribute("wisski_id", wisskiID);
}
var filename = container.getAttribute("3d").split("/").pop();
var basename = filename.substring(0, filename.lastIndexOf('.'));
var extension = filename.substring(filename.lastIndexOf('.') + 1);	
var path = container.getAttribute("3d").substring(0, container.getAttribute("3d").lastIndexOf(filename));
// BEGIN - part necessary to keep while updating
var fileSize;
// END - part necessary to keep while updating
const uri = path.replace(CONFIG.domain+"/", "");
const EXPORT_PATH = '/export_xml_single/';
const loadedFile = basename + "." + extension;
var COPYRIGHTS = false;
const allowedFormats = ['obj', 'fbx', 'ply', 'dae', 'ifc', 'stl', 'xyz', 'pcd', 'json', '3ds'];
var EXIT_CODE=1;
var gridSize;

var canvasText;
var downloadModel, viewEntity, fullscreenMode;

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
statsContainer.style.position = 'absolute';
statsContainer.style.left = '3%';
container.appendChild(statsContainer);

var guiContainer = document.createElement("div");
guiContainer.id = 'guiContainer';
guiContainer.className = 'guiContainer';
// BEGIN - part necessary to keep while updating
guiContainer.style.position = 'absolute';
guiContainer.style.right = '2%';
guiContainer.style.marginTop = '0px';
// END - part necessary to keep while updating
var guiElement = document.createElement("div");
guiElement.id = 'guiElement';
guiElement.className = 'guiElement';
guiContainer.appendChild(guiElement);
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
var RULER_MODE = false;
const lineMaterial = new THREE.LineBasicMaterial( { color: 0x0000ff } );
var linePoints = [];

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
var textMeshDistance;
var ruler = [];
var rulerObject;
var lastPickedFace = {id: '', color: '', object: ''};

function readWissKI () {
	const xmlhttp = new XMLHttpRequest();
	xmlhttp.onload = function() {
		//console.log(this.responseText);
	};
	xmlhttp.open("GET", "php/fetchWissKI.php?q=");
	xmlhttp.send();
}

function readMetadataFromFile(responseText) {
	const parser = new DOMParser();
	const doc = parser.parseFromString(responseText, "application/xml");
	var data;
	for (let childNode of doc.documentElement.childNodes) {
		data = childNode.childNodes;
		if (typeof (data) !== undefined && data.length > 0) {
			break;
		}
	}
	return data;
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
	// BEGIN - path can't be changed while updating
	loader.load( '/typo3conf/ext/dlf/Resources/Public/JavaScript/3DViewer/fonts/helvetiker_regular.typeface.json', function ( font ) {
	// END - path can't be changed while updating

		const textGeo = new TextGeometry( _text, {
			font,
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

		//const centerOffset = - 0.5 * ( textGeo.boundingBox.max.x - textGeo.boundingBox.min.x );

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

function addTextPoint (_text, _scale, _point) {
	var textGeo;
	var materials = [
		new THREE.MeshStandardMaterial( { color: 0x0000ff, flatShading: true, side: THREE.DoubleSide, depthTest: false, depthWrite: false, transparent: true, opacity: 0.4 } ), // front
		new THREE.MeshStandardMaterial( { color: 0x0000ff, flatShading: true, side: THREE.DoubleSide, depthTest: false, depthWrite: false, transparent: true, opacity: 0.4 } ) // side
	];
	const loader = new FontLoader();
	// BEGIN - path can't be changed while updating
	loader.load( '/typo3conf/ext/dlf/Resources/Public/JavaScript/3DViewer/fonts/helvetiker_regular.typeface.json', function ( font ) {
	// END - path can't be changed while updating

		const textGeo = new TextGeometry( _text, {
			font,
			size: _scale*3,
			height: _scale/10,
			curveSegments: 4,
			bevelEnabled: true,
			bevelThickness: _scale/8,
			bevelSize: _scale/10,
			bevelOffset: 0,
			bevelSegments: 1
		} );
		textGeo.computeBoundingBox();

		//const centerOffset = - 0.5 * ( textGeo.boundingBox.max.x - textGeo.boundingBox.min.x );

		textMeshDistance = new THREE.Mesh( textGeo, materials );

		//textMeshDistance.rotation.z = Math.PI;
		//textMeshDistance.rotation.y = Math.PI;
		
		textMeshDistance.position.set(_point.x, _point.y, _point.z);
		textMeshDistance.renderOrder = 1;
		rulerObject.add(textMeshDistance);
		//scene.add( textMesh );		
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

function recreateBoundingBox (object) {
	var _min = new THREE.Vector3();
	var _max = new THREE.Vector3();
	if (object instanceof THREE.Object3D)
	{
		object.traverse (function (mesh)
		{
			if (mesh instanceof THREE.Mesh)
			{
				mesh.geometry.computeBoundingBox ();
				var bBox = mesh.geometry.boundingBox;

				// compute overall bbox
				_min.x = Math.min (_min.x, bBox.min.x + mesh.position.x);
				_min.y = Math.min (_min.y, bBox.min.y + mesh.position.y);
				_min.z = Math.min (_min.z, bBox.min.z + mesh.position.z);
				_max.x = Math.max (_max.x, bBox.max.x + mesh.position.x);
				_max.y = Math.max (_max.y, bBox.max.y + mesh.position.y);
				_max.z = Math.max (_max.z, bBox.max.z + mesh.position.z);
			}
		});

		var bBoxMin = new THREE.Vector3 (_min.x, _min.y, _min.z);
		var bBoxMax = new THREE.Vector3 (_max.x, _max.y, _max.z);
		var bBoxNew = new THREE.Box3 (bBoxMin, bBoxMax);
		object.position.set((bBoxNew.min.x+bBoxNew.max.x)/2, bBoxNew.min.y, (bBoxNew.min.z+bBoxNew.max.z)/2);
	}
	return object;
}

function setupObject (_object, _camera, _light, _data, _controls) {
	if (typeof (_data) !== "undefined") {
		_object.position.set (_data["objPosition"][0], _data["objPosition"][1], _data["objPosition"][2]);
		_object.scale.set (_data["objScale"][0], _data["objScale"][1], _data["objScale"][2]);
		_object.rotation.set (THREE.MathUtils.degToRad(_data["objRotation"][0]), THREE.MathUtils.degToRad(_data["objRotation"][1]), THREE.MathUtils.degToRad(_data["objRotation"][2]));
		_object.needsUpdate = true;
		if (typeof (_object.geometry) !== "undefined") {
			_object.geometry.computeBoundingBox();
			_object.geometry.computeBoundingSphere();	
		}
	}
	else {
		var boundingBox = new THREE.Box3();
		if (Array.isArray(_object)) {
			for (let i = 0; i < _object.length; i++) {
				boundingBox.setFromObject( _object[i] );
				_object[i].position.set(-(boundingBox.min.x+boundingBox.max.x)/2, -boundingBox.min.y, -(boundingBox.min.z+boundingBox.max.z)/2);
				//_object[i].position.set (0, 0, 0);
				_object[i].needsUpdate = true;
				if (typeof (_object[i].geometry) !== "undefined") {
					_object[i].geometry.computeBoundingBox();
					_object[i].geometry.computeBoundingSphere();	
				}			
			}
		}
		else {
			boundingBox.setFromObject( _object );
			_object.position.set(-(boundingBox.min.x+boundingBox.max.x)/2, -boundingBox.min.y, -(boundingBox.min.z+boundingBox.max.z)/2);
			//_object.position.set (0, 0, 0);
			_object.needsUpdate = true;
			if (typeof (_object.geometry) !== "undefined") {
				_object.geometry.computeBoundingBox();
				_object.geometry.computeBoundingSphere();
			}
		}
	}
	cameraLightTarget.position.set(_object.position.x, _object.position.y, _object.position.z);
	cameraLight.target.updateMatrixWorld();
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
		renderer.localClippingEnabled = true;
		clippingPlanes[ 0 ].constant = value;
		render();
	});


	clippingFolder.add( planeParams.planeY, 'displayHelperY' ).onChange( (v) => { planeHelpers[ 1 ].visible = v; renderer.localClippingEnabled = v; } );
	clippingFolder.add( planeParams.planeY, 'constant' ).min( - distanceGeometry.y ).max( distanceGeometry.y ).setValue(distanceGeometry.y).step(_size/100).listen().onChange(function (value) {
		renderer.localClippingEnabled = true;
		clippingPlanes[ 1 ].constant = value;
		render();
	});


	clippingFolder.add( planeParams.planeZ, 'displayHelperZ' ).onChange( (v) => { planeHelpers[ 2 ].visible = v; renderer.localClippingEnabled = v; } );
	clippingFolder.add( planeParams.planeZ, 'constant' ).min( - distanceGeometry.z ).max( distanceGeometry.z ).setValue(distanceGeometry.z).step(_size/100).listen().onChange(function (value) {
		renderer.localClippingEnabled = true;
		clippingPlanes[ 2 ].constant = value;
		render();
	});
}

function fitCameraToCenteredObject (camera, object, offset, orbitControls, _fit ) {
	const boundingBox = new THREE.Box3();
	if (Array.isArray(object)) {
		for (let i = 0; i < object.length; i++) {			
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
	fileElement[0].style.height = canvasDimensions.y*1.5 + "px";
	var mainElement = document.getElementById(CONFIG.galleryContainer);
	var imageElements = document.getElementsByClassName(CONFIG.galleryImageClass);
	if (imageElements.length > 0) {
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
		};

		document.addEventListener('click', function(event) {
			if (!modalGallery.contains(event.target) && !imageList.contains(event.target)) {
				//event.preventDefault();
				modalGallery.style.display = "none";
				zoomImage = 1.5;
				modalImage.style.transform = `scale(1.5)`;
			}
		});

		modalGallery.appendChild(modalImage);
		modalGallery.appendChild(modalClose);
		for (let i = 0; imageElements.length - i; imageList.firstChild === imageElements[0] && i++) {
			//imageElements[i].className += " image-list-item";
			var imgList = imageElements[i].getElementsByTagName("a");
			for (let j = 0; j < imgList.length; j++) {
				imgList[j].setAttribute("href", "#");
				imgList[j].setAttribute("src", imgList[j].firstChild.src);
				imgList[j].setAttribute("class", "image-list-item");
			}
			imgList = imageElements[i].getElementsByTagName("img");
			for (let j = 0; j < imgList.length; j++) {
				imgList[j].onclick = function(){
					modalGallery.style.display = "block";
					modalImage.src = this.src;
				};
			}
			//imageElements[i].getElementsByTagName("a")[0].setAttribute("href", "#");
			imageList.appendChild(imageElements[i]);
		}
		fileElement[0].insertAdjacentElement('beforebegin', modalGallery);
		mainElement.insertBefore(imageList, fileElement[0]);
	}
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
			for (let i = 0; i < _object.length; i++) {
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

function distanceBetweenPoints(pointA, pointB) {
	return Math.sqrt(Math.pow(pointB.x - pointA.x, 2) + Math.pow(pointB.y - pointA.y, 2) + Math.pow(pointB.z - pointA.z, 2) ,2);
}

function distanceBetweenPointsVector(vector) {
	return Math.sqrt(Math.pow(vector.x, 2) + Math.pow(vector.y, 2) + Math.pow(vector.z, 2) ,2);
}

function vectorBetweenPoints (pointA, pointB) {
	return new THREE.Vector3(pointB.x - pointA.x, pointB.y - pointA.y, pointB.z - pointA.z);
}

function halfwayBetweenPoints(pointA, pointB) {
	return new THREE.Vector3((pointB.x + pointA.x)/2, (pointB.y + pointA.y)/2, (pointB.z + pointA.z)/2);
}

function interpolateDistanceBetweenPoints(pointA, vector, length, scalar) {
	var _x = pointA.x + (scalar/Math.abs(length)) * vector.x;
	var _y = pointA.y + (scalar/Math.abs(length)) * vector.y;
	var _z = pointA.z + (scalar/Math.abs(length)) * vector.z;
	return new THREE.Vector3(_x, _y, _z);
}

function pickFaces(_id) {
	if (lastPickedFace.id === '' && _id !== '') {
		lastPickedFace = {id: _id, color: _id.object.material.color.getHex(), object: _id.object.id};
	}
	else if (_id === '' && lastPickedFace.id !== '') {
		scene.getObjectById(lastPickedFace.object).material.color.setHex(lastPickedFace.color);
		lastPickedFace = {id: '', color: '', object: ''};
	}
	else if (_id !== lastPickedFace.id) {
		scene.getObjectById(lastPickedFace.object).material.color.setHex(lastPickedFace.color);
		lastPickedFace = {id: _id, color: _id.object.material.color.getHex(), object: _id.object.id};		
	}
	if (_id !== '') {
		_id.object.material.color.setHex(0xFF0000);
	}
}

function buildRuler(_id) {
	rulerObject = new THREE.Object3D();
	var sphere = new THREE.Mesh(new THREE.SphereGeometry(gridSize/150, 7, 7), new THREE.MeshNormalMaterial({
				transparent : true,
				opacity : 0.8,
				side: THREE.DoubleSide, depthTest: false, depthWrite: false
			}));
	var newPoint = new THREE.Vector3( _id.point.x, _id.point.y, _id.point.z );
	sphere.position.set( newPoint.x, newPoint.y, newPoint.z	);
	rulerObject.add(sphere);
	linePoints.push( newPoint );
	const lineGeometry = new THREE.BufferGeometry().setFromPoints( linePoints );
	const line = new THREE.Line( lineGeometry, lineMaterial );
	rulerObject.add( line );
	var lineMtr = new THREE.LineBasicMaterial({ color: 0x0000FF, linewidth: 3, opacity: 1, side: THREE.DoubleSide, depthTest: false, depthWrite: false });
	if (linePoints.length > 1) {
		var vectorPoints = vectorBetweenPoints(linePoints[linePoints.length-2], newPoint);
		var distancePoints = distanceBetweenPointsVector(vectorPoints);
		
		//var distancePoints = distanceBetweenPoints(linePoints[linePoints.length-2], newPoint);
		var halfwayPoints = halfwayBetweenPoints(linePoints[linePoints.length-2], newPoint);
		addTextPoint(distancePoints.toFixed(2), gridSize/200, halfwayPoints);
		var rulerI = 0;
		var measureSize = gridSize/400;
        while (rulerI <= distancePoints*100) {
            const geoSegm = [];
			var interpolatePoints = interpolateDistanceBetweenPoints(linePoints[linePoints.length-2], vectorPoints, distancePoints, rulerI/100);
            geoSegm.push(new THREE.Vector3(interpolatePoints.x, interpolatePoints.y, interpolatePoints.z));
            //geoSegm.push(new THREE.Vector3(interpolatePoints.x+_id.face.normal.x, interpolatePoints.y+_id.face.normal.y, interpolatePoints.z+_id.face.normal.z));
			geoSegm.push(new THREE.Vector3(interpolatePoints.x+measureSize, interpolatePoints.y+measureSize, interpolatePoints.z+measureSize));
			const geometryLine = new THREE.BufferGeometry().setFromPoints( geoSegm );
            var lineSegm = new THREE.Line(geometryLine, lineMtr);
			rulerObject.add(lineSegm);
            //var textSprite = makeTextSprite((i * 10).toString(), {r: 255, g: 255, b: 255, a: 255}, new THREE.Vector3(0.2, ruler, 3), Math.PI);
            //ruler.add(textSprite);
            rulerI+=10;
        }
	}
	rulerObject.renderOrder = 1;
	scene.add(rulerObject);
	ruler.push(rulerObject);
}

function onWindowResize() {
	// BEGIN - values can't be changed while updating
	var rightOffsetDownload = -74;
	var rightOffsetEntity = -77;
	var rightOffsetFullscreen = 1;
	if (FULLSCREEN) {
		canvasDimensions = {x: screen.width, y: screen.height};
		rightOffsetDownload = -80.5;
		rightOffsetEntity = -83.5;
		rightOffsetFullscreen = 1;
		guiContainer.style.left = canvasDimensions.x - 260 + 'px';
	}
	else {
		canvasDimensions = {x: window.self.innerWidth*0.8, y: window.self.innerHeight};
		guiContainer.style.left = canvasDimensions.x - 380 + 'px';
	}
	container.setAttribute("width", canvasDimensions.x);
	container.setAttribute("height", canvasDimensions.y);

	mainCanvas.setAttribute("width", canvasDimensions.x);
	mainCanvas.setAttribute("height", canvasDimensions.y);
	mainCanvas.style.width = "100% !important";
	mainCanvas.style.height = "100% !important";

	guiContainer.style.top = mainCanvas.offsetTop + 'px';

	renderer.setPixelRatio( window.devicePixelRatio );
	camera.aspect = canvasDimensions.x / canvasDimensions.y;
	camera.updateProjectionMatrix();
	renderer.setSize( canvasDimensions.x, canvasDimensions.y );
	downloadModel.setAttribute('style', 'right: ' + rightOffsetDownload +'%');
	viewEntity.setAttribute('style', 'right: ' + rightOffsetEntity +'%');

	fullscreenMode.setAttribute('style', 'bottom:' + Math.round(-canvasDimensions.y + 55) + 'px');
	// END - values can't be changed while updating
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
				_str = "";
			break;
		}
		if (_str === "period") {
			return "Reconstruction period: <b>"+value+" - ";
		}
		else if (_str === "-") {
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

function expandMetadata () {
   const el = document.getElementById("metadata-content");
   el.classList.toggle('expanded');
   const elm = document.getElementById("metadata-collapse");
   elm.classList.toggle('metadata-collapsed');
}

function fullscreen() {
	FULLSCREEN=!FULLSCREEN;
	//var _container = document.getElementById("MainCanvas");
	var _container = container;
	if (FULLSCREEN) {
		if (_container.requestFullscreen ) {
			_container.requestFullscreen();
		}
		else if (_container.webkitRequestFullscreen) { /* Safari */
			_container.webkitRequestFullscreen();
		}
		else if (_container.msRequestFullscreen) { /* IE11 */
			_container.msRequestFullscreen();
		}
		else if (_container.mozRequestFullScreen) { /* Mozilla */
			_container.mozRequestFullScreen();
		}
	}
	else
	{
		if (document.exitFullscreen) {
			document.exitFullscreen();
		}
		else if (document.webkitExitFullscreen) { /* Safari */
			document.webkitExitFullscreen();
		}
		else if (document.msExitFullscreen) { /* IE11 */
			document.msExitFullscreen();
		}
	}
	onWindowResize();
}

function exitFullscreenHandler() {
	var fullscreenElement = document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement;
	var fullscreenElement2 = document.webkitIsFullScreen && document.mozFullScreen && document.msFullscreenElement;
	if (!fullscreenElement && typeof(fullscreenElement2 === undefined) && FULLSCREEN) {
		fullscreen();
	}
}

function fetchSettings ( path, basename, filename, object, camera, light, controls, orgExtension, extension ) {
	var metadata = {'vertices': 0, 'faces': 0};
	var hierarchy = [];
	var geometry;
	var metadataUrl = path + "metadata/" + filename + "_viewer";
	if (proxy) {
		metadataUrl = settingsPath;
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
	.then((data) => {
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
						tempArray = {["Mesh"]() {selectObjectHierarchy(child.id);}, 'id': child.id};
					}
					else {
						tempArray = { [shortChildName]() {selectObjectHierarchy(child.id);}, 'id': child.id};
					}
					hierarchyFolder = hierarchyMain.addFolder(shortChildName).close();
					hierarchyFolder.add(tempArray, shortChildName);
					clippingGeometry.push(child.geometry);
					child.traverse( function ( children ) {
						if ( children.isMesh &&  children.name !== child.name) {
							var shortChildrenName = truncateString(children.name, GUILength);
							if (children.name === '') {
								tempArray = {["Mesh"] (){selectObjectHierarchy(children.id);}, 'id': children.id};
							}
							else {
								tempArray = { [shortChildrenName] (){selectObjectHierarchy(children.id);}, 'id': children.id};
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
				tempArray = {["Mesh"] (){selectObjectHierarchy(object.id);}, 'id': object.id};
				object.name = object.id;
			}
			else {
				tempArray = {[object.name] (){selectObjectHierarchy(object.id);}, 'id': object.id};
			}
			//hierarchy.push(tempArray);
			if (object.name === "undefined") {object.name = "level";}
			clippingGeometry.push(object.geometry);
			hierarchyFolder = hierarchyMain.addFolder(object.name).close();
			//hierarchyFolder.add(tempArray, 'name' ).name(object.name);
			metadata['vertices'] += fetchMetadata (object, 'vertices');
			metadata['faces'] += fetchMetadata (object, 'faces');
		}

		hierarchyMain.domElement.classList.add("hierarchy");
		
		var metadataContainer = document.createElement('div');
		metadataContainer.setAttribute('id', 'metadata-container');

		var metadataContent = '<div id="metadata-collapse" class="metadata-collapse metadata-collapsed">METADATA </div><div id="metadata-content" class="metadata-content expanded">';
		metadataContentTech = '<hr class="metadataSeparator">';
		metadataContentTech += 'Uploaded file name: <b>' + basename + "." + orgExtension + '</b><br>';
		metadataContentTech += 'Loaded format: <b>' + extension + '</b><br>';
		metadataContentTech += 'Vertices: <b>' + metadata['vertices'] + '</b><br>';
		metadataContentTech += 'Faces: <b>' + metadata['faces'] + '</b><br>';

		// BEGIN - part necessary to keep while updating
		var metadataPath = CONFIG.metadataDomain + EXPORT_PATH + wisskiID + '?page=0&amp;_format=xml';
		if (proxy) {
			metadataPath = xmlPath;
		}
		// END - part necessary to keep while updating

		var req = new XMLHttpRequest();
		req.responseType = 'xml';
		req.open('GET', metadataPath, true);
		req.onreadystatechange = function (aEvt) {
			if (req.readyState === 4) {
				if(req.status === 200) {
					var data = readMetadataFromFile(req.responseText);
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
					downloadModel = document.createElement('div');
					downloadModel.setAttribute('id', 'downloadModel');
					viewEntity = document.createElement('div');
					viewEntity.setAttribute('id', 'viewEntity');
					var cPath = path;
					//if (compressedFile !== '') { cPath = CONFIG.domain + '/' + uri; }
					if (compressedFile !== '') { filename = filename.replace(orgExtension, extension); }
					downloadModel.innerHTML = "<a href='" + cPath + filename + "' download><img src='/typo3conf/ext/dlf/Resources/Public/JavaScript/3DViewer/img/cloud-arrow-down.svg' alt='download' width=25 height=25 title='Download source file'/></a>";
					
					if (proxy) {
						viewEntity.innerHTML = "<a href='" + CONFIG.domain + "/wisski/navigate/" + wisskiID + "/view' target='_blank'><img src='/typo3conf/ext/dlf/Resources/Public/JavaScript/3DViewer/img/share.svg' alt='View Entity' width=22 height=22 title='View Entity'/></a>";
					}
					else
					{
						metadataContainer.appendChild( downloadModel );
					}
					metadataContainer.appendChild( viewEntity );
					fullscreenMode = document.createElement('div');
					fullscreenMode.setAttribute('id', 'fullscreenMode');
					// BEGIN - values can't be changed while updating
					fullscreenMode.setAttribute('style', 'bottom:' + Math.round(-canvasDimensions.y + 85) + 'px');
					// END - values can't be changed while updating
					// BEGIN - path can't be changed while updating
					fullscreenMode.innerHTML = "<img src='/typo3conf/ext/dlf/Resources/Public/JavaScript/3DViewer/img/fullscreen.png' alt='Fullscreen' width=20 height=20 title='Fullscreen mode'/>";
					// END - path can't be changed while updating
					metadataContainer.appendChild(fullscreenMode);
					//var _container = document.getElementById("MainCanvas");
					container.appendChild(metadataContainer);
					document.getElementById ("metadata-collapse").addEventListener ("click", expandMetadata, false);
					document.getElementById ("fullscreenMode").addEventListener ("click", fullscreen, false);
					if (document.addEventListener) {
						document.addEventListener('webkitfullscreenchange', exitFullscreenHandler, false);
						document.addEventListener('mozfullscreenchange', exitFullscreenHandler, false);
						document.addEventListener('fullscreenchange', exitFullscreenHandler, false);
						document.addEventListener('MSFullscreenChange', exitFullscreenHandler, false);
					}
				}
				else {
					showToast("Error during loading metadata content");
				}
			}
		};
		req.send(null);
		//hierarchyFolder.add(hierarchyText, 'Faces' );
	});
	helperObjects.push (object);
	//addTextWatermark("©", object.scale.x);
	//lightObjects.push (object);
}

const onError = function (_event) {
	//circle.set(100, 100);
	//console.log("Loader error: " + _event);
	circle.hide();
	EXIT_CODE=1;
};

const onProgress = function ( xhr ) {
	// BEGIN - part necessary to keep while updating
	var percentComplete;
	if (xhr.lengthComputable) {
		percentComplete = xhr.loaded / xhr.total * 100;
	} else {
		percentComplete = xhr.loaded / fileSize * 100;
	}
	if (percentComplete !== Infinity) {
		circle.show();
		circle.set(percentComplete, 100);
		if (percentComplete >= 100) {
			circle.hide();
			showToast("Model has been loaded.");
			EXIT_CODE=0;
		}
	} else {
		if (circle) {
			circle.hide();
			showToast("Model has been loaded.");
		}
	}
	// END - part necessary to keep while updating
};

function loadModel ( path, basename, filename, extension, orgExtension ) {
	if (!imported) {
		circle.show();
		circle.set(0, 100);
		var modelPath = path + filename;
		// BEGIN - part necessary to keep while updating
		if (proxy) {
			modelPath = model;
		}

		var req = new XMLHttpRequest();
		req.open('HEAD', modelPath, false);
		req.onreadystatechange = function (aEvt) {
			if (req.readyState === 4) {
				fileSize = req.getResponseHeader("Content-Length");
			}
		};
		req.send(null);
		// END - part necessary to keep while updating

		switch(extension.toLowerCase()) {
			case 'obj':
				const manager = new THREE.LoadingManager();
				manager.onLoad = function ( ) { showToast ("OBJ model has been loaded"); };
				manager.addHandler( /\.dds$/i, new DDSLoader() );
				// manager.addHandler( /\.tga$/i, new TGALoader() );
				new MTLLoader( manager )
					//.setPath( path )
					.load( modelPath, function ( materials ) {
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
				const ifcLoader = new IFCLoader();
				// BEGIN - path can't be changed while updating
				ifcLoader.ifcManager.setWasmPath( '/typo3conf/ext/dlf/Resources/Public/JavaScript/3DViewer/js/jsm/loaders/ifc/' );
				// END - path can't be changed while updating
				ifcLoader.load( modelPath, function ( object ) {
					//object.position.set (0, 300, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'stl':
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
				loader = new XYZLoader();
				loader.load( modelPath, function ( geometry ) {
					geometry.center();
					const vertexColors = ( geometry.hasAttribute( 'color' ) === true );
					const material = new THREE.PointsMaterial( { size: 0.1, vertexColors } );
					const object = new THREE.Points( geometry, material );
					object.position.set (0, 0, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;

			case 'pcd':
				loader = new PCDLoader();
				loader.load( modelPath, function ( mesh ) {
					scene.add( mesh );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;

			case 'json':
				loader = new THREE.ObjectLoader();
				loader.load(
					modelPath, function ( object ) {
						object.position.set (0, 0, 0);
						scene.add( object );
						fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, orgExtension, extension );
						mainObject.push(object);
					}, onProgress, onError );
			break;

			case '3ds':
				loader = new TDSLoader( );
				loader.setResourcePath( path );
				// BEGIN - part necessary to keep while updating
				modelPath = path + basename + "." + extension;
				if (proxy) {
					modelPath = model;
				}
				loader.load( modelPath, function ( object ) {
				// END - part necessary to keep while updating
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
			case 'rar':
			case 'tar':
			case 'gz':
			case 'xz':
				showToast("Model is being loaded from compressed archive.");
			break;
			
			case 'glb':
			case 'gltf':
				const dracoLoader = new DRACOLoader();
				// BEGIN - path can't be changed while updating
				dracoLoader.setDecoderPath( '/typo3conf/ext/dlf/Resources/Public/JavaScript/3DViewer/js/libs/draco/' );
				// END - path can't be changed while updating
				dracoLoader.preload();
				const gltf = new GLTFLoader();
				gltf.setDRACOLoader(dracoLoader);
				showToast("Trying to load model from " + extension + " representation.");

				modelPath = path + basename + "." + extension;
				if (proxy) {
					modelPath = model;
				}

				gltf.load(modelPath, function(gltf) {
					gltf.scene.traverse( function ( child ) {
						if ( child.isMesh ) {
							child.castShadow = true;
							child.receiveShadow = true;
							child.geometry.computeVertexNormals();
							if(child.material.map) { child.material.map.anisotropy = 16; }
							child.material.side = THREE.DoubleSide;
							child.material.clippingPlanes = clippingPlanes;
							child.material.clipIntersection = false;
							mainObject.push(child);	
						}
					});
					fetchSettings (path, basename, filename, gltf.scene, camera, lightObjects[0], controls, orgExtension, extension );
					scene.add( gltf.scene );
				},
					function ( xhr ) {
						// BEGIN - part necessary to keep while updating
						var percentComplete;
						if (xhr.lengthComputable) {
							percentComplete = xhr.loaded / xhr.total * 100;
						} else {
							percentComplete = xhr.loaded / fileSize * 100;
						}
						if (percentComplete !== Infinity) {
							circle.show();
							circle.set(percentComplete, 100);
							if (percentComplete >= 100) {
								circle.hide();
								showToast("Model " + filename + " has been loaded.");
							}
						} else {
							if (circle) {
								circle.hide();
								showToast("Model " + filename + " has been loaded.");
							}
						}
						// END - part necessary to keep while updating
					}/*,
					function ( ) {						
							showToast("GLTF or file with given name (possible archive/filename mismatch) representation not found, trying original file [semi-automatic]...");
							showToast(path.replace("gltf/", "") + filename + " [" + orgExtension + "]");
							var autoBasename = basename.replace(/_[0-9]+$/, '');
							if (EXIT_CODE != 0) {
								loadModel (path, autoBasename, '', 'glb', orgExtension);
								if (EXIT_CODE != 0) {
									allowedFormats.forEach(function(item, index, array) {
										if (EXIT_CODE != 0) {
											loadModel (path.replace("gltf/", ""), autoBasename, filename, item, orgExtension); 
										}
									});
								}
							}
							if (EXIT_CODE != 0) {
								allowedFormats.forEach(function(item, index, array) {
									if (EXIT_CODE != 0) {
										circle.show();
										loadModel (path.replace("gltf/", ""), basename, filename, item, orgExtension);
									}
								});
							}

							//loadModel(path.replace("gltf/", ""), basename, filename, orgExtension, orgExtension);
							imported = true;
					}*/
				);
			break;
			default:
				showToast("Extension not supported yet");
		}
	}
	else {
		showToast("File " + path + basename + " not found.");
		//circle.set(100, 100);
		//circle.hide();
	}
	
	scene.updateMatrixWorld();
}

//

function animate() {
	requestAnimationFrame( animate );
	const delta = clock.getDelta();
	if ( mixer ) { mixer.update( delta ); }
	TWEEN.update();
	/*for ( let i = 0; i < clippingPlanes.length && clippingMode; i ++ ) {
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
	}*/
	if (textMesh !== undefined) { textMesh.lookAt(camera.position); }
	renderer.render( scene, camera );
	stats.update();
}

function updateObject () {
}

function onPointerDown( e ) {
	//onDownPosition.x = event.clientX;
	//onDownPosition.y = event.clientY;
	if (e.button === 0) {
		onDownPosition.x = ((e.clientX - container.getBoundingClientRect().left)/ renderer.domElement.clientWidth) * 2 - 1;
		onDownPosition.y = - ((e.clientY - container.getBoundingClientRect().top) / renderer.domElement.clientHeight) * 2 + 1;
	}
}

function onPointerUp( e ) {
    //onUpPosition.x = ( e.clientX / canvasDimensions.x ) * 2 - 1;
    //onUpPosition.y = -( e.clientY / canvasDimensions.y ) * 2 + 1;
    //onUpPosition.x = ( e.clientX / (canvasDimensions.x - container.offsetLeft)) * 2 - 1;
    //onUpPosition.y = -( e.clientY / (canvasDimensions.y - container.offsetTop)) * 2 + 1;
	if (e.button === 0) {
		onUpPosition.x = ((e.clientX - container.getBoundingClientRect().left)/ renderer.domElement.clientWidth) * 2 - 1;
		onUpPosition.y = - ((e.clientY - container.getBoundingClientRect().top) / renderer.domElement.clientHeight) * 2 + 1;
		
		if (onUpPosition.x === onDownPosition.x && onUpPosition.y === onDownPosition.y) {
			raycaster.setFromCamera( onUpPosition, camera );
			var intersects;
			if (EDITOR || RULER_MODE) {
				if (mainObject.length > 1) {
					for (let ii = 0; ii < mainObject.length; ii++) {
						intersects = raycaster.intersectObjects( mainObject[ii].children, true );
					}
					if (intersects.length <= 0) {
						intersects = raycaster.intersectObjects( mainObject, true );
					}
				}
				else {
					intersects = raycaster.intersectObjects( mainObject[0], true );
				}
				if (intersects.length > 0) {
					if (RULER_MODE) {buildRuler(intersects[0]);}
					else if (EDITOR) {pickFaces(intersects[0]);}
				}
			}
		}
	}
}

function onPointerMove( e ) {
	pointer.x = ((e.clientX - container.getBoundingClientRect().left)/ renderer.domElement.clientWidth) * 2 - 1;
	pointer.y = - ((e.clientY - container.getBoundingClientRect().top) / renderer.domElement.clientHeight) * 2 + 1;
	if (e.buttons === 1) {
		if (pointer.x !== onDownPosition.x && pointer.y !== onDownPosition.y) {
			cameraLight.position.set(camera.position.x, camera.position.y, camera.position.z);
		}
	}
	if (e.buttons !== 1) {
		if (EDITOR) {
			raycaster.setFromCamera( pointer, camera );
			var intersects;
		
			if (mainObject.length > 1) {
				for (let ii = 0; ii < mainObject.length; ii++) {
					intersects = raycaster.intersectObjects( mainObject[ii].children, true );
				}
				if (intersects.length <= 0) {
					intersects = raycaster.intersectObjects( mainObject, true );
				}
			}
			else {
				intersects = raycaster.intersectObjects( mainObject[0], true );
			}
			if (intersects.length > 0) {
				pickFaces(intersects[0]);
			}
			else {
				pickFaces("");
			}
		}
	}
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
		for (let i = 0; i < helperObjects[0].length; i++) {			
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

function mainLoadModel (_ext) {
	if (_ext === "glb" || _ext === "gltf") {
		loadModel (path, basename, filename, extension, _ext);
	}
	else if  (_ext === "zip" || _ext === "rar" || _ext === "tar" || _ext === "xz" || _ext === "gz" ) {
		compressedFile = "_" + _ext.toUpperCase() + "/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename,  "glb", _ext);
	}
	else {
		if (_ext === "glb") {
			loadModel (path, basename, filename, "glb", extension);
		}
		else {
			loadModel (path, basename, filename, _ext, extension);
		}
	}
}

function init() {
	// model
	//canvasDimensions = {x: container.getBoundingClientRect().width, y: container.getBoundingClientRect().bottom};
	// BEGIN - values can't be changed while updating
	canvasDimensions = {x: window.self.innerWidth*0.8, y: window.self.innerHeight};
	// END - values can't be changed while updating
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
	
	cameraLightTarget = new THREE.Object3D();
	cameraLightTarget.position.set(camera.position.x, camera.position.y, camera.position.z);
	scene.add(cameraLightTarget);

	cameraLight = new THREE.DirectionalLight( 0xffffff );
	cameraLight.position.set( camera.position );
	cameraLight.castShadow = false;
	cameraLight.intensity = 0.3;
	scene.add( cameraLight );
	cameraLight.target = cameraLightTarget;
	cameraLight.target.updateMatrixWorld();

	renderer = new THREE.WebGLRenderer( { antialias: true, logarithmicDepthBuffer: true, colorManagement: true, sortObjects: true, preserveDrawingBuffer: true, powerPreference: "high-performance" } );
	renderer.setPixelRatio( window.devicePixelRatio );
	renderer.setSize( canvasDimensions.x, canvasDimensions.y );
	renderer.shadowMap.enabled = true;
	renderer.localClippingEnabled = false;
	renderer.setClearColor( 0x263238 );
	renderer.domElement.id = 'MainCanvas';
	container.appendChild( renderer.domElement );
	mainCanvas = document.getElementById("MainCanvas");
	
	canvasText = document.createElement('div');
	canvasText.id = "TextCanvas";
	canvasText.width = canvasDimensions.x;
	canvasText.height = canvasDimensions.y;

	//DRUPAL WissKI [start]
	if (!dfgViewer) {
		buildGallery();
	}
	//DRUPAL WissKI [end]

	controls = new OrbitControls( camera, renderer.domElement );
	controls.target.set( 0, 100, 0 );
	controls.update();
	
	transformControl = new TransformControls( camera, renderer.domElement );
	transformControl.rotationSnap = THREE.MathUtils.degToRad(5);
	transformControl.space = "local";
	transformControl.addEventListener( 'change', render );
	transformControl.addEventListener( 'objectChange', changeScale );
	transformControl.addEventListener( 'mouseUp', calculateObjectScale );
	transformControl.addEventListener( 'dragging-changed', function ( event ) {
		controls.enabled = ! event.value;
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
	
	var _ext = extension.toLowerCase();

	// BEGIN - part necessary to keep while updating
	var metadataPath = CONFIG.metadataDomain + EXPORT_PATH + wisskiID + '?page=0&amp;_format=xml';
	if (proxy) {
		metadataPath = xmlPath;
	}
	// END - part necessary to keep while updating

	var req = new XMLHttpRequest();
	req.responseType = 'xml';
	req.open('GET', metadataPath, true);
	req.onreadystatechange = function (aEvt) {
		if (req.readyState === 4) {
			if(req.status === 200) {
				var data = readMetadataFromFile(req.responseText);
				if (typeof (data) !== undefined) {
					var _found = false;
					for(var i = 0; i < data.length && !_found; i++) {
						if ((typeof (data[i].tagName) !== "undefined") && (typeof (data[i].textContent) !== "undefined")) {							
							var _label = data[i].tagName.replace("wisski_path_3d_model__", "");
							if (typeof(_label) !== "undefined" && _label === "converted_file_name") {
								_found = true;
								var _autoPath = data[i].textContent.trim();
								//check whether semi-automatic path found
								if (_autoPath !== '') {							
									filename = _autoPath.split("/").pop().trim();
									basename = filename.substring(0, filename.lastIndexOf('.')).trim();
									extension = filename.substring(filename.lastIndexOf('.') + 1).trim();
									_ext = extension.toLowerCase().trim();
									path = _autoPath.substring(0, _autoPath.lastIndexOf(filename)).trim();
								}
								mainLoadModel(_ext);
							}
						}
					}
				} else {
					showToast("Error during loading metadata content - empty metadata file\n");
				}
			}
			else {
				showToast("Error during loading metadata content\n");
				mainLoadModel (_ext);
			}
		}
	};
	req.send(null);
	/*try {

	} catch (e) {
		// statements to handle any exceptions
		loadModel(path, basename, filename, extension);
	}*/


	container.addEventListener( 'pointerdown', onPointerDown );
	container.addEventListener( 'pointerup', onPointerUp );
	container.addEventListener( 'pointermove', onPointerMove );
	window.addEventListener( 'resize', onWindowResize );

	// stats
	stats = new Stats();
	stats.domElement.style.cssText = 'position:relative;top:0px;left:-80px;max-height:120px;max-width:90px;z-index:2;';
	container.appendChild( stats.dom );
	
	windowHalfX = canvasDimensions.x / 2;
	windowHalfY = canvasDimensions.y / 2;
	
	const editorFolder = gui.addFolder('Editor').close();
	editorFolder.add(transformText, 'Transform 3D Object', { None: '', Move: 'translate', Rotate: 'rotate', Scale: 'scale' } ).onChange(function (value)
	{ 
		if (value === '') { transformControl.detach(); } 
		else {
			renderer.localClippingEnabled = false;
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
				jsonRequestURL = CONFIG.domain + "/editor.php";

			xhr.open(method, jsonRequestURL, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			var params;
			var rotateMetadata = new THREE.Vector3(THREE.MathUtils.radToDeg(helperObjects[0].rotation.x),THREE.MathUtils.radToDeg(helperObjects[0].rotation.y),THREE.MathUtils.radToDeg(helperObjects[0].rotation.z));
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
		editorFolder.add({["Picking mode"] () {
			EDITOR=!EDITOR;
			var _str;
			EDITOR ? _str = "enabled" : _str = "disabled";
			showToast ("Face picking is " + _str);
			if (EDITOR) {
				RULER_MODE = false;
			}
		}}, 'Picking mode');
		editorFolder.add({["Distance Measurement"] () {
			RULER_MODE=!RULER_MODE;
			var _str;
			RULER_MODE ? _str = "enabled" : _str = "disabled";
			showToast ("Distance measurement mode is " + _str);
			if (!RULER_MODE) {
				
				ruler.forEach( (r) => {
					scene.remove(r);
				});
				rulerObject = new THREE.Object3D();
				ruler = [];
				linePoints = [];
			}
			else {
				EDITOR = false;
			}
		}}, 'Distance Measurement');
		clippingFolder = editorFolder.addFolder('Clipping Planes').close();
	}
}

window.onload = function() {
	init();
	animate();
};
