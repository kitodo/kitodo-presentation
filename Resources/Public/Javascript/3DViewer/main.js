//Supported file formats: OBJ, DAE, FBX, PLY, IFC, STL, XYZ, JSON, 3DS, glTF

const path = '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer';
//const path = '..'; //local

import * as THREE from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/three.module.js';
import { TWEEN } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/libs/tween.module.min.js';

import Stats from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/libs/stats.module.js';

import { OrbitControls } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/controls/OrbitControls.js';
import { TransformControls } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/controls/TransformControls.js';
import { GUI } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/lil-gui/dist/lil-gui.esm.min.js';
import { FBXLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/FBXLoader.js';
import { DDSLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/DDSLoader.js';
import { MTLLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/MTLLoader.js';
import { OBJLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/OBJLoader.js';
import { GLTFLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/DRACOLoader.js'
import { KTX2Loader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/KTX2Loader.js';
import { MeshoptDecoder } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/libs/meshopt_decoder.module.js';
import { IFCLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/IFCLoader.js';
import { PLYLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/PLYLoader.js';
import { ColladaLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/ColladaLoader.js';
import { STLLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/STLLoader.js';
import { XYZLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/XYZLoader.js';
import { TDSLoader } from '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/TDSLoader.js';

let camera, scene, renderer, stats, controls, loader;
let imported;
var mainObject = [];

const clock = new THREE.Clock();
const editor = true;

let mixer;

const supportedFormats = [ 'OBJ', 'DAE', 'FBX', 'PLY', 'IFC', 'STL', 'XYZ', 'JSON' ];

const container = document.getElementById("DFG_3DViewer");
var spinnerContainer = document.createElement("div");
spinnerContainer.id = 'spinnerContainer';
spinnerContainer.className = 'spinnerContainer';
spinnerContainer.style.position = 'absolute';
spinnerContainer.style.left = '35%';
spinnerContainer.style.marginTop = '10px';
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
statsContainer.style.right = '93%';
container.appendChild(statsContainer);

var guiContainer = document.createElement("div");
guiContainer.id = 'guiContainer';
guiContainer.className = 'guiContainer';
guiContainer.style.position = 'absolute';
guiContainer.style.left = '80%';
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
let transformControl, transformControlLight;

const helperObjects = [];
const lightObjects = [];

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
}

const colors = {
	Light1: '0xFFFFFF'
}

const intensity = { startIntensity: 1 }

var EDITOR = false;

const gui = new GUI({ container: guiContainer });
const metadataFolder = gui.addFolder('Metadata');
//const mainHierarchyFolder = gui.addFolder('Hierarchy');
var hierarchyFolder;
const GUILength = 35;

var canvasDimensions;
var compressedFile = '';
//guiContainer.appendChild(gui.domElement);

var options = {
    duration: 6500,
	gravity: "bottom",
	close: true,
    callback: function(){
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
var planeObjects = [];

var clippingGeometry = [];
let clippingObject = new THREE.Group();
const planeGeom = new THREE.PlaneGeometry( 4, 4 );

init();
animate();

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

function init() {
	
	canvasDimensions = {x: container.getBoundingClientRect().width, y: container.getBoundingClientRect().bottom};
	container.setAttribute("width", canvasDimensions.x);
	container.setAttribute("height", canvasDimensions.y);

	camera = new THREE.PerspectiveCamera( 45, canvasDimensions.x / canvasDimensions.y, 1, 200000 );
	camera.position.set( 0, 0, 0 );

	scene = new THREE.Scene();
	scene.background = new THREE.Color( 0xa0a0a0 );
	scene.fog = new THREE.Fog( 0xa0a0a0, 90000, 1000000 );

	const hemiLight = new THREE.HemisphereLight( 0xffffff, 0x444444 );
	hemiLight.position.set( 0, 200, 0 );
	scene.add( hemiLight );

	const dirLight = new THREE.DirectionalLight( 0xffffff );
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

	// scene.add( new THREE.CameraHelper( dirLight.shadow.camera ) );

	renderer = new THREE.WebGLRenderer( { antialias: true } );
	renderer.setPixelRatio( window.devicePixelRatio );
	renderer.setSize( canvasDimensions.x, canvasDimensions.y );
	renderer.shadowMap.enabled = true;
	renderer.localClippingEnabled = true;
	renderer.setClearColor( 0x263238 );
	container.appendChild( renderer.domElement );

	controls = new OrbitControls( camera, renderer.domElement );
	controls.target.set( 0, 100, 0 );
	controls.update();
	
	transformControl = new TransformControls( camera, renderer.domElement );
	transformControl.addEventListener( 'change', render );
	transformControl.addEventListener( 'dragging-changed', function ( event ) {
		controls.enabled = ! event.value;
	} );
	scene.add( transformControl );
	
	transformControlLight = new TransformControls( camera, renderer.domElement );
	transformControlLight.addEventListener( 'change', render );
	transformControlLight.addEventListener( 'dragging-changed', function ( event ) {
		controls.enabled = ! event.value;
	} );
	scene.add( transformControlLight );

	// model
	const filename = container.getAttribute("3d").split("/").pop();
	const basename = filename.substring(0, filename.lastIndexOf('.'));
	const extension = filename.substring(filename.lastIndexOf('.') + 1);	
	const path = container.getAttribute("3d").substring(0, container.getAttribute("3d").lastIndexOf(filename));
	const domain = "https://3d-repository.hs-mainz.de";
	const uri = path.replace(domain+"/", "");

	/*try {

	} catch (e) {
		// statements to handle any exceptions
		console.log("No glTF file, loading original file.");
		loadModel(path, basename, filename, extension);
	}*/
	if (extension == "glb" || extension == "GLB" || extension == "gltf" || extension == "GLTF") {
		loadModel (path, basename, filename, extension, extension);
	}
	else if  (extension == "zip" || extension == "ZIP" ) {
		compressedFile = "_ZIP/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else if  (extension == "rar" || extension == "RAR" ) {
		compressedFile = "_RAR/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else if  (extension == "tar" ) {
		compressedFile = "_TAR/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else if  (extension == "xz" ) {
		compressedFile = "_XZ/";
		loadModel (path+basename+compressedFile+"gltf/", basename, filename, "glb", extension);
	}
	else if  (extension == "gz" ) {
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
	stats.domElement.style.cssText = 'position:absolute;top:0px;left:0px;max-height:120px;max-width:90px;';
	container.appendChild( stats.dom );
	
	windowHalfX = canvasDimensions.x / 2;
	windowHalfY = canvasDimensions.y / 2;
	
	const editorFolder = gui.addFolder('Editor').close();
	editorFolder.add(transformText, 'Transform 3D Object', { None: '', Move: 'translate', Rotate: 'rotate', Scale: 'scale' } ).onChange(function (value)
	{ 
		if (value == '') transformControl.detach(); else { transformControl.mode = value; transformControl.attach( helperObjects[0] ); }
	});
	const lightFolder = editorFolder.addFolder('Lights');
	lightFolder.add(transformText, 'Transform Light', { None: '', Move: 'translate', Rotate: 'rotate', Scale: 'scale' } ).onChange(function (value)
	{ 
		if (value == '') transformControlLight.detach(); else { transformControlLight.mode = value; transformControlLight.attach( lightObjects[0] ); }
	});
	lightFolder.addColor ( colors, 'Light1' ).onChange(function (value) {
		const tempColor = new THREE.Color( value );
		lightObjects[0].color = tempColor ;
	});
	lightFolder.add( intensity, 'startIntensity', 0, 10 ).onChange(function (value) {
		lightObjects[0].intensity = value;
	});
	clippingFolder = editorFolder.addFolder('Clipping Planes');

	if (editor)
		editorFolder.add({["Save"]: function(){
			var xhr = new XMLHttpRequest(),
				jsonArr,
				method = "POST",
				jsonRequestURL = "https://3d-repository.hs-mainz.de/editor.php";

			xhr.open(method, jsonRequestURL, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			//console.log(camera.position);
			var rotateMetadata = new THREE.Vector3(THREE.Math.radToDeg(helperObjects[0].rotation.x),THREE.Math.radToDeg(helperObjects[0].rotation.y),THREE.Math.radToDeg(helperObjects[0].rotation.z));
			var newMetadata = ({"objPosition": [ helperObjects[0].position.x, helperObjects[0].position.y, helperObjects[0].position.z ], "objScale": [ helperObjects[0].scale.x, helperObjects[0].scale.y, helperObjects[0].scale.z ], "objRotation": [ rotateMetadata.x, rotateMetadata.y, rotateMetadata.z ], "camPosition": [ camera.position.x, camera.position.y, camera.position.z ], "camLookAt": [ 0, 0, 0 ], "lightPosition": [ dirLight.position.x, dirLight.position.y, dirLight.position.z ], "lightColor": [ "#" + (dirLight.color.getHexString()).toUpperCase() ], "lightIntensity": [ dirLight.intensity ] });
			console.log(uri+basename+"/");
			if (compressedFile != '')
				var params = "5MJQTqB7W4uwBPUe="+JSON.stringify(newMetadata, null, '\t')+"&path="+uri+basename+compressedFile+"&filename="+filename;
			else
				var params = "5MJQTqB7W4uwBPUe="+JSON.stringify(newMetadata, null, '\t')+"&path="+uri+"&filename="+filename;
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
		editorFolder.add({["Picking"]: function(){
			EDITOR=!EDITOR;
			var _str;
			EDITOR ? _str = "enabled" : _str = "disabled";
			showToast ("Face picking is " + _str);
		}}, 'Picking');
}

function loadModel ( path, basename, filename, extension, org_extension ) {
	if (!imported) {
		circle.show();
		circle.set(0, 100);
		switch(extension) {
			case 'obj':
			case 'OBJ':
				const manager = new THREE.LoadingManager();
				manager.onLoad = function ( ) { showToast ("OBJ model has been loaded"); }
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
								fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, org_extension, extension );
								mainObject.push(object);
							}, onProgress, onError );
					} );
			break;
			
			case 'fbx':
			case 'FBX':
				var FBXloader = new FBXLoader();
				FBXloader.load( path + filename, function ( object ) {
					object.traverse( function ( child ) {
						if ( child.isMesh ) {
							child.castShadow = true;
							child.receiveShadow = true;
						}
					} );
					object.position.set (0, 0, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object.children, camera, controls, org_extension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'ply':
			case 'PLY':
				loader = new PLYLoader();
				loader.load( path + filename, function ( geometry ) {
					geometry.computeVertexNormals();
					const material = new THREE.MeshStandardMaterial( { color: 0x0055ff, flatShading: true } );
					const object = new THREE.Mesh( geometry, material );
					object.position.set (0, 0, 0);
					object.castShadow = true;
					object.receiveShadow = true;
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, org_extension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'dae':
			case 'DAE':
				const loadingManager = new THREE.LoadingManager( function () {
					scene.add( object );
				} );
				loader = new ColladaLoader( loadingManager );
				loader.load( path + filename, function ( object ) {
					object = object.scene;
					object.position.set (0, 0, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, org_extension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'ifc':
			case 'IFC':
				const ifcLoader = new IFCLoader();
				ifcLoader.setWasmPath( '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/jsm/loaders/ifc/' );
				ifcLoader.load( path + filename, function ( object ) {
					//object.position.set (0, 300, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, org_extension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;
			
			case 'stl':
			case 'STL':
				loader = new STLLoader();
				loader.load( path + filename, function ( geometry ) {
					let meshMaterial = new THREE.MeshPhongMaterial( { color: 0xff5533, specular: 0x111111, shininess: 200 } );
					if ( geometry.hasColors ) {
						meshMaterial = new THREE.MeshPhongMaterial( { opacity: geometry.alpha, vertexColors: true } );
					}
					const object = new THREE.Mesh( geometry, meshMaterial );
					object.position.set (0, 0, 0);
					object.castShadow = true;
					object.receiveShadow = true;
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, org_extension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;

			case 'xyz':
			case 'XYZ':
				loader = new XYZLoader();
				loader.load( path + filename, function ( geometry ) {
					geometry.center();
					const vertexColors = ( geometry.hasAttribute( 'color' ) === true );
					const material = new THREE.PointsMaterial( { size: 0.1, vertexColors: vertexColors } );
					object = new THREE.Points( geometry, material );
					object.position.set (0, 0, 0);
					scene.add( object );
					fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, org_extension, extension );
					mainObject.push(object);
				}, onProgress, onError );
			break;

			case 'json':
			case 'JSON':
				loader = new THREE.ObjectLoader();
				loader.load(
					path + filename, function ( object ) {
						object.position.set (0, 0, 0);
						scene.add( object );
						fetchSettings (path.replace("gltf/", ""), basename, filename, object, camera, lightObjects[0], controls, org_extension, extension );
						mainObject.push(object);
					}, onProgress, onError );
			break;

			case '3ds':
			case '3DS':
				loader = new TDSLoader( );
				loader.setResourcePath( path );
				loader.load( path + filename, function ( object ) {
					object.traverse( function ( child ) {
						if ( child.isMesh ) {
							//child.material.specular.setScalar( 0.1 );
							//child.material.normalMap = normal;
						}
					} );
					scene.add( object );
					mainObject.push(object);
				} );
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
				console.log("Uncompressed files are loaded");
			break;
			
			case 'glb':
			case 'GLB':
			case 'gltf':
			case 'GLTF':
				const dracoLoader = new DRACOLoader();
				dracoLoader.setDecoderPath( '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/draco/' );
				dracoLoader.preload();
				const gltf = new GLTFLoader();
				gltf.setDRACOLoader(dracoLoader);
				//console.log("[i] Loading model from " + extension + " representation.");
				showToast("Trying to load model from " + extension + " representation.");

				const glbPath = path + basename + "." + extension;
				gltf.load(glbPath, function(gltf) {
					gltf.scene.traverse( function ( child ) {
						if ( child.isMesh ) {
							child.castShadow = true;
							child.receiveShadow = true;
							child.geometry.computeVertexNormals();
							if(child.material.map) child.material.map.anisotropy = 16;
							child.material.side = THREE.DoubleSide;
							for ( let i = 0; i < 3; i ++ ) {

								const poGroup = new THREE.Group();
								const plane = clippingPlanes[ i ];
								const stencilGroup = createClippingPlaneGroup( child.geometry, plane, i + 1 );

								// plane is clipped by the other clipping planes
								const planeMat =
									new THREE.MeshStandardMaterial( {

										color: 0xE91E63,
										metalness: 0.1,
										roughness: 0.75,
										clippingPlanes: clippingPlanes.filter( p => p !== plane ),

										stencilWrite: true,
										stencilRef: 0,
										stencilFunc: THREE.NotEqualStencilFunc,
										stencilFail: THREE.ReplaceStencilOp,
										stencilZFail: THREE.ReplaceStencilOp,
										stencilZPass: THREE.ReplaceStencilOp,

									} );
								const po = new THREE.Mesh( planeGeom, planeMat );
								po.onAfterRender = function ( renderer ) {

									renderer.clearStencil();

								};

								po.renderOrder = i + 1.1;

								clippingObject.add( stencilGroup );
								poGroup.add( po );
								planeObjects.push( po );
								scene.add( poGroup );

							}
							child.material.clippingPlanes = clippingPlanes;
							child.material.clipIntersection = false;
							mainObject.push(child);	
						}
					});
					fetchSettings (path.replace("gltf/", ""), basename, filename, gltf.scene, camera, lightObjects[0], controls, org_extension, extension );
					scene.add( gltf.scene );
				},
					function ( xhr ) {
						var percentComplete = xhr.loaded / xhr.total * 100;
						if (percentComplete != Infinity) {
							//console.log( ( percentComplete ) + '% loaded' );
							circle.set(percentComplete, 100);
							if (percentComplete >= 100) {
								circle.hide();
								//console.log("[i] Model " + filename + " has been loaded.");
								showToast("Model " + filename + " has been loaded.");
							}
						}
					},
					function ( ) {						
							//console.log("[i] GLTF representation not found, trying original file " + path.replace("gltf/", "") + filename + " [" + org_extension + "]");
							showToast("GLTF representation not found, trying original file " + path.replace("gltf/", "") + filename + " [" + org_extension + "]");
							loadModel(path.replace("gltf/", ""), basename, filename, org_extension, org_extension);
							imported = true;
					}
				);
			break;
			default:
				//console.log("[i] Extension not supported yet");
				showToast("Extension not supported yet");
		}
	}
	else {
		//console.log("File " + path + basename + " not found.");
		showToast("File " + path + basename + " not found.");
		circle.set(100, 100);
		circle.hide();
	}
	
	scene.updateMatrixWorld();
}

function fetchSettings ( path, basename, filename, object, camera, light, controls, org_extension, extension ) {
	var metadata = {'vertices': 0, 'faces': 0};
	var hierarchy = [];
	var geometry;
	//console.log(path + "@" + basename + "@" +  filename);
	fetch(path + "metadata/" + filename + "_viewer", {cache: "no-cache"})
	.then(response => {
		if (response['status'] != "404") {
			//console.log("Metadata " + path + "metadata/" + filename + "_viewer found");
			showToast("Settings " + filename + "_viewer found");
			return response.json();
		}
		else if (response['status'] == "404") {
			//console.log("No metadata " + path + "metadata/" + filename + "_viewer found");
			showToast("No settings " + filename + "_viewer found");
		}
		})
	.then(data => {
		var tempArray = [];
		const hierarchyMain = gui.addFolder( 'Hierarchy' ).close();
		if (object.name == "Scene" || object.children.length > 0 ) {
			setupObject(object, camera, light, data, controls);
			object.traverse( function ( child ) {
				if ( child.isMesh ) {
					metadata['vertices'] += fetchMetadata (child, 'vertices');
					metadata['faces'] += fetchMetadata (child, 'faces');
					var shortChildName = truncateString(child.name, GUILength);
					if (child.name == '')
						tempArray = {["Mesh"]: function(){selectObjectHierarchy(child.id)}, 'id': child.id};
					else
						tempArray = { [shortChildName]: function(){selectObjectHierarchy(child.id)}, 'id': child.id};
					hierarchyFolder = hierarchyMain.addFolder(shortChildName).close();
					hierarchyFolder.add(tempArray, shortChildName);
					clippingGeometry.push(child.geometry);
					child.traverse( function ( children ) {
						if ( children.isMesh &&  children.name != child.name) {
							var shortChildrenName = truncateString(children.name, GUILength);
							if (children.name == '')
								tempArray = {["Mesh"]: function(){selectObjectHierarchy(children.id)}, 'id': children.id};
							else
								tempArray = { [shortChildrenName]: function(){selectObjectHierarchy(children.id)}, 'id': children.id}; 
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
			if (object.name == '')
				tempArray = {["Mesh"]: function(){selectObjectHierarchy(object.id)}, 'id': object.id};
			else
				tempArray = {[object.name]: function(){selectObjectHierarchy(object.id)}, 'id': object.id};
			//hierarchy.push(tempArray);
			clippingGeometry.push(object.geometry);
			hierarchyFolder = hierarchyMain.addFolder(object.name).close();
			hierarchyFolder.add(tempArray, 'name' ).name(object.name);
			metadata['vertices'] += fetchMetadata (object, 'vertices');
			metadata['faces'] += fetchMetadata (object, 'faces');
		}
		var loadedFile = basename + "." + extension;
		var metadataText =
		{
			'Original extension': org_extension.toUpperCase(),
			'Loaded file': loadedFile,
			Vertices: metadata['vertices'],
			Faces: metadata['faces']
		}
		hierarchyMain.domElement.classList.add("hierarchy");

		metadataFolder.add(metadataText, 'Original extension' );
		metadataFolder.add(metadataText, 'Loaded file' );
		metadataFolder.add(metadataText, 'Vertices' );
		metadataFolder.add(metadataText, 'Faces' );
		//hierarchyFolder.add(hierarchyText, 'Faces' );
	});
	helperObjects.push (object);

	//lightObjects.push (object);
}

function selectObjectHierarchy (_id) {
	let search = true;
	for (let i = 0; i < selectedObjects.length && search == true; i++ ) {
		if (selectedObjects[i].id == _id) {
			search = false;
			if (selectedObjects[i].selected == true) {
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
			if (typeof (_object.geometry.index) != "undefined" && _object.geometry.index != null)
				return _object.geometry.index.count;
			else if (typeof (_object.attributes) != "undefined" && _object.attributes != null)
				return _object.attributes.position.count;
		case 'faces':
			if (typeof (_object.geometry.index) != "undefined" && _object.geometry.index != null)
				return _object.geometry.index.count/3;
			else if (typeof (_object.attributes) != "undefined" && _object.attributes != null)
				return _object.attributes.position.count/3;
		break;
	}
}

function setupObject (_object, _camera, _light, _data, _controls) {
	if (typeof (_data) != "undefined") {
		_object.position.set (_data["objPosition"][0], _data["objPosition"][1], _data["objPosition"][2]);
		_object.scale.set (_data["objScale"][0], _data["objScale"][1], _data["objScale"][2]);
		_object.rotation.set (THREE.Math.degToRad(_data["objRotation"][0]), THREE.Math.degToRad(_data["objRotation"][1]), THREE.Math.degToRad(_data["objRotation"][2]));
		_object.needsUpdate = true;
		if (typeof (_object.geometry) != "undefined") {
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
				if (typeof (_object[i].geometry) != "undefined") {
					_object[i].geometry.computeBoundingBox();
					_object[i].geometry.computeBoundingSphere();	
				}			
			}
		}
		else {
			boundingBox.setFromObject( _object );
			_object.position.set (0, 0, 0);
			_object.needsUpdate = true;
			if (typeof (_object.geometry) != "undefined") {
				_object.geometry.computeBoundingBox();
				_object.geometry.computeBoundingSphere();
			}
		}
	}

}

function setupCamera (_object, _camera, _light, _data, _controls) {
	if (typeof (_data) != "undefined") {
		_camera.position.set( _data["camPosition"][0], _data["camPosition"][1], _data["camPosition"][2] );
		//_light.position.set( _data["lightPosition"][0], _data["lightPosition"][1], _data["lightPosition"][2] );
		//_light.color = new THREE.Color( _data["lightColor"][0] );
		//_light.intensity = _data["lightIntensity"][0];
		_camera.updateProjectionMatrix();
		_controls.update();
		fitCameraToCenteredObject ( _camera, _object, 1.7, _controls );
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
		fitCameraToCenteredObject ( _camera, _object, 1.7, _controls );
	}
}

function pickFaces(_id) {
	var sphere = new THREE.Mesh(new THREE.SphereGeometry(0.1, 7, 7), new THREE.MeshNormalMaterial({
				transparent : true,
				opacity : 0.8
			}));
	sphere.position.set(_id[0].point.x, _id[0].point.y, _id[0].point.z);
	scene.add(sphere);
	//console.log(_id[0]);
	/*if (mainObject.name == "Scene" || mainObject.children.length > 0)
		mainObject.traverse( function ( child ) {
			if (child.isMesh) {
				console.log(child);
				child.traverse( function ( children ) {
					console.log(children.geometry);
				});
			}
		});
	else
		var intersects = raycaster.intersectObjects( mainObject, false );*/
}

function onWindowResize() {
	camera.aspect = canvasDimensions.x / canvasDimensions.y;
	camera.updateProjectionMatrix();
	renderer.setSize( canvasDimensions.x, canvasDimensions.y );
	render();
}

//

function animate() {
	requestAnimationFrame( animate );
	const delta = clock.getDelta();
	if ( mixer ) mixer.update( delta );
	TWEEN.update();
	for ( let i = 0; i < clippingPlanes.length; i ++ ) {

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
	renderer.render( scene, camera );
	stats.update();
}

function fitCameraToCenteredObject (camera, object, offset, orbitControls ) {
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
	console.log("Centering camera");
	// ground
	var distance = new THREE.Vector3(Math.abs(boundingBox.max.x - boundingBox.min.x), Math.abs(boundingBox.max.y - boundingBox.min.y), Math.abs(boundingBox.max.z - boundingBox.min.z));
	var gridSize = Math.max(distance.x, distance.y, distance.z);
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

    // offset the camera, if desired (to avoid filling the whole canvas)
    if( offset !== undefined && offset !== 0 ) cameraZ *= offset;
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
    const cameraToFarEdge = ( minZ < 0 ) ? -minZ + cameraZ : cameraZ - minZ;

    camera.far = cameraToFarEdge * 3;
    camera.updateProjectionMatrix();

    if ( orbitControls !== undefined ) {
        // set camera to rotate around the center
        orbitControls.target = new THREE.Vector3(0, 0, 0);

        // prevent camera from zooming out far enough to create far plane cutoff
        orbitControls.maxDistance = cameraToFarEdge * 2;
    }
	controls.update();
	
	setupClippingPlanes(object.geometry, gridSize, distance);
}

function setupClippingPlanes (_geometry, _size, _distance) {	
	clippingPlanes[ 0 ].constant = _distance.x;
	clippingPlanes[ 1 ].constant = _distance.y;
	clippingPlanes[ 2 ].constant = _distance.z;

	planeHelpers = clippingPlanes.map( p => new THREE.PlaneHelper( p, _size*2, 0xffffff ) );
	planeHelpers.forEach( ph => {
		ph.visible = false;
		ph.name = "PlaneHelper";
		scene.add( ph );
	} );

	clippingFolder.add( planeParams.planeX, 'displayHelperX' ).onChange( v => planeHelpers[ 0 ].visible = v );
	clippingFolder.add( planeParams.planeX, 'constant' ).min( - _distance.x ).max( _distance.x ).setValue(_distance.x).step(_size/100).onChange(function (value) {
		clippingPlanes[ 0 ].constant = value;
		render();
	});


	clippingFolder.add( planeParams.planeY, 'displayHelperY' ).onChange( v => planeHelpers[ 1 ].visible = v );
	clippingFolder.add( planeParams.planeY, 'constant' ).min( - _distance.y ).max( _distance.y ).setValue(_distance.y).step(_size/100).onChange(function (value) {
		clippingPlanes[ 1 ].constant = value;
		render();
	});


	clippingFolder.add( planeParams.planeZ, 'displayHelperZ' ).onChange( v => planeHelpers[ 2 ].visible = v );
	clippingFolder.add( planeParams.planeZ, 'constant' ).min( - _distance.z ).max( _distance.z ).setValue(_distance.z).step(_size/100).onChange(function (value) {
		clippingPlanes[ 2 ].constant = value;
		render();
	});
}

function render() {
	controls.update();
	renderer.render( scene, camera );
}

function updateObject () {
	//console.log(helperObjects[0].position);
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
	//console.log(onUpPosition);
	var mouseVector = new THREE.Vector2();
	//onUpPosition.x = ((e.clientX - container.offsetLeft) / canvasDimensions.x) * 2 - 1;
	//onUpPosition.y =  - ((e.clientY - (container.offsetTop - document.body.scrollTop + 11)) / (canvasDimensions.y)) * 2 + 1;
	raycaster.setFromCamera( pointer, camera );
	var intersects;
	if (EDITOR) {
		if (mainObject.name == "Scene" || mainObject.length > 1)
			/*for (let ii = 0; ii < mainObject.length; ii++) {	
				intersects = raycaster.intersectObjects( mainObject[ii].children, false );
			}*/
			intersects = raycaster.intersectObjects( mainObject[0].children, false );
		else
			intersects = raycaster.intersectObjects( mainObject[0], false );
		//console.log(pointer);
		console.log(intersects);
		if (intersects.length > 0)
			pickFaces(intersects);
	}
}

function onPointerMove( event ) {
	//pointer.x = (event.clientX / renderer.domElement.clientWidth) * 2 - 1;
	//pointer.y = -(event.clientY / renderer.domElement.clientHeight) * 2 + 1;
	//console.log(container.getBoundingClientRect());
	pointer.x = ( ( event.clientX - container.getBoundingClientRect().left ) / (canvasDimensions.x - 200 )) * 2 - 1;
	pointer.y = - ((event.clientY - (container.getBoundingClientRect().top - document.body.scrollTop - 50)) / (canvasDimensions.y)) * 2 + 1;
	/*pointer.x = ( event.clientX - windowHalfX ) / canvasDimensions.x;
	pointer.y = ( event.clientY - windowHalfY ) / canvasDimensions.y;
	console.log(pointer);
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

const onError = function () {
	circle.set(100, 100);
	circle.hide();	
};

const onProgress = function ( xhr ) {
	var percentComplete = xhr.loaded / xhr.total * 100;
	//console.log( ( percentComplete ) + '% loaded' );					
	circle.set(percentComplete, 100);
	if (percentComplete >= 100) {
		circle.hide();
		showToast("Model has been loaded.");
	}
};

function truncateString(str, n) {
	if (str.length == 0) return str;
	else if (str.length > n) {
		return str.substring(0, n) + "...";
	} else {
		return str;
	}
}

function showToast (_str) {
	var myToast = Toastify(options);
	myToast.options.text = _str;
	myToast.showToast();
}