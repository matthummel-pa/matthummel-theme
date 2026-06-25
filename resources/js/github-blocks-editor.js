(function (wp) {
  if (!wp || !wp.blocks) return;
  var el = wp.element.createElement, Fragment = wp.element.Fragment, __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor, IC = be.InspectorControls, ubp = be.useBlockProps;
  var c = wp.components, SSR = wp.serverSideRender || wp.components.ServerSideRender;
  var owner = (window.mhGithubBlocks && window.mhGithubBlocks.owner) || '';

  function setter(props){ return function(k){ return function(v){ var o={}; o[k]=v; props.setAttributes(o); }; }; }
  function preview(name, a){ return el('div', ubp ? ubp() : {}, el(SSR, { block: name, attributes: a })); }

  wp.blocks.registerBlockType('mh/repo-card', {
    apiVersion:2, title:__('GitHub Repo Card','matthummel'), icon:'media-code', category:'widgets',
    keywords:['github','repo','card'], supports:{align:['wide','full']},
    attributes:{ owner:{type:'string',default:''}, repo:{type:'string',default:''}, showDesc:{type:'boolean',default:true}, showStats:{type:'boolean',default:true} },
    edit:function(props){ var a=props.attributes, set=setter(props);
      return el(Fragment,{}, el(IC,{}, el(c.PanelBody,{title:__('Repository','matthummel'),initialOpen:true},
        el(c.TextControl,{label:__('Owner','matthummel'),value:a.owner,onChange:set('owner'),placeholder:owner}),
        el(c.TextControl,{label:__('Repo','matthummel'),value:a.repo,onChange:set('repo')}),
        el(c.ToggleControl,{label:__('Description','matthummel'),checked:!!a.showDesc,onChange:set('showDesc')}),
        el(c.ToggleControl,{label:__('Stats','matthummel'),checked:!!a.showStats,onChange:set('showStats')})
      )), preview('mh/repo-card', a)); },
    save:function(){ return null; }
  });

  wp.blocks.registerBlockType('mh/repo-grid', {
    apiVersion:2, title:__('GitHub Repo Grid','matthummel'), icon:'grid-view', category:'widgets',
    keywords:['github','repos','grid'], supports:{align:['wide','full']},
    attributes:{ username:{type:'string',default:''}, count:{type:'number',default:6}, columns:{type:'number',default:2}, sort:{type:'string',default:'updated'} },
    edit:function(props){ var a=props.attributes, set=setter(props);
      return el(Fragment,{}, el(IC,{}, el(c.PanelBody,{title:__('Repositories','matthummel'),initialOpen:true},
        el(c.TextControl,{label:__('Username / org','matthummel'),value:a.username,onChange:set('username'),placeholder:owner}),
        el(c.RangeControl,{label:__('How many','matthummel'),value:a.count,min:1,max:30,onChange:set('count')}),
        el(c.RangeControl,{label:__('Columns','matthummel'),value:a.columns,min:1,max:4,onChange:set('columns')}),
        el(c.SelectControl,{label:__('Sort by','matthummel'),value:a.sort,options:[
          {label:'Recently updated',value:'updated'},{label:'Recently pushed',value:'pushed'},{label:'Name',value:'full_name'},{label:'Created',value:'created'}],onChange:set('sort')})
      )), preview('mh/repo-grid', a)); },
    save:function(){ return null; }
  });

  wp.blocks.registerBlockType('mh/gh-stats', {
    apiVersion:2, title:__('GitHub Profile Stats','matthummel'), icon:'admin-users', category:'widgets',
    keywords:['github','stats','profile'], supports:{align:['wide','full']},
    attributes:{ username:{type:'string',default:''}, showAvatar:{type:'boolean',default:true}, showBio:{type:'boolean',default:true} },
    edit:function(props){ var a=props.attributes, set=setter(props);
      return el(Fragment,{}, el(IC,{}, el(c.PanelBody,{title:__('Profile','matthummel'),initialOpen:true},
        el(c.TextControl,{label:__('Username / org','matthummel'),value:a.username,onChange:set('username'),placeholder:owner}),
        el(c.ToggleControl,{label:__('Avatar','matthummel'),checked:!!a.showAvatar,onChange:set('showAvatar')}),
        el(c.ToggleControl,{label:__('Bio','matthummel'),checked:!!a.showBio,onChange:set('showBio')})
      )), preview('mh/gh-stats', a)); },
    save:function(){ return null; }
  });

  wp.blocks.registerBlockType('mh/gh-releases', {
    apiVersion:2, title:__('GitHub Releases','matthummel'), icon:'tag', category:'widgets',
    keywords:['github','releases','changelog'], supports:{align:['wide','full']},
    attributes:{ owner:{type:'string',default:''}, repo:{type:'string',default:''}, count:{type:'number',default:5} },
    edit:function(props){ var a=props.attributes, set=setter(props);
      return el(Fragment,{}, el(IC,{}, el(c.PanelBody,{title:__('Releases','matthummel'),initialOpen:true},
        el(c.TextControl,{label:__('Owner','matthummel'),value:a.owner,onChange:set('owner'),placeholder:owner}),
        el(c.TextControl,{label:__('Repo','matthummel'),value:a.repo,onChange:set('repo')}),
        el(c.RangeControl,{label:__('How many','matthummel'),value:a.count,min:1,max:20,onChange:set('count')})
      )), preview('mh/gh-releases', a)); },
    save:function(){ return null; }
  });
})(window.wp);
