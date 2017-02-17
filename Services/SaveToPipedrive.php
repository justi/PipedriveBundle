<?php

namespace Justi\PipedriveBundle\Services;

use Devio\Pipedrive\Pipedrive;
use Justi\PipedriveBundle\DataObjects\PipedriveDeal;
use Justi\PipedriveBundle\DataObjects\PipedriveNote;
use Justi\PipedriveBundle\DataObjects\PipedrivePerson;
use Justi\PipedriveBundle\DataObjects\PipedrivePipeline;
use Justi\PipedriveBundle\DataObjects\PipedriveStage;
use Symfony\Component\Serializer\Serializer;

class SaveToPipedrive
{
    private $serializer;
    private $pipedrive;

    /**
     * @param Serializer $serializer
     * @param  \Devio\Pipedrive\Pipedrive $pipedrive
     * @internal param EntityManager $em
     */
    public function __construct(Serializer $serializer, Pipedrive $pipedrive)
    {
        $this->serializer = $serializer;
        $this->pipedrive = $pipedrive;
    }


    public function savePerson(PipedrivePerson $person) {
        /** @var PipedrivePerson $foundPerson */
        $foundPerson = $this->getPerson($person->getEmail());
        if (null === $foundPerson) {
            return $this->addPerson($person);
        }
        $person->id = $foundPerson->getId();
        return $this->updatePerson($person);
    }

    public function getPerson($name){
        $response = $this->pipedrive->persons->findByName($name);
        $people = $response->getData();
        if (null !== $people) {
            return $this->serializer->deserialize(json_encode($people[0]), PipedrivePerson::class, 'json');
        }
    }

    public function addPerson(PipedrivePerson $person){
        $normalizedData = $this->serializer->normalize($person);
        $response = $this->pipedrive->persons->add($normalizedData);
        if (null !== $response->getData()) {
            return $this->serializer->deserialize(json_encode($response->getData()), PipedrivePerson::class, 'json');
        }
    }

    public function updatePerson(PipedrivePerson $person){
        $normalizedData = $this->serializer->normalize($person);
        $responseResult = $this->apiUpdatePerson($person->getId(), $normalizedData);
        if ($responseResult) {
            return $this->serializer->deserialize(json_encode($responseResult), PipedrivePerson::class, 'json');
        }
    }

    public function getDeal($name){
        $response = $this->pipedrive->deals->findByName($name);
        $deals = $response->getData();
        if (null !== $deals) {
            /** @var PipedriveDeal $deal */
            return $this->serializer->deserialize(json_encode($deals[0]), PipedriveDeal::class, 'json');
        }
    }

    public function getDealDetails($dealId){
        $response = $this->pipedrive->deals->find($dealId);
        $deal = $response->getData();
        if (null !== $deal) {
            /** @var PipedriveDeal $deal */
            return $this->serializer->deserialize(json_encode($deal), PipedriveDeal::class, 'json');
        }
    }

    public function saveDeal(PipedriveDeal $deal){
        /** @var PipedrivePerson $foundPerson */
        $foundDeal = $this->getDeal($deal->title);
        if (null === $foundDeal) {
            return $this->addDeal($deal);
        }
        $deal->id = $foundDeal->getId();
        return $this->updateDeal($deal);
    }

    public function saveDealWithNote(PipedriveDeal $deal){
        /** @var PipedriveDeal $foundDeal */
        $foundDeal = $this->getDeal($deal->title);
        if (null === $foundDeal) {
            return $this->addDeal($deal);
        }
        $foundDeal = $this->getDealDetails($foundDeal->getId());
        $deal->id = $foundDeal->getId();
        if ($deal->stage_id != $foundDeal->stage_id) {
            $note = new PipedriveNote;
            $note->deal_id = $deal->getId();
            $note->content = "<b>Stage changed from ".$foundDeal->stage_id." to ".$deal->stage_id."</b>";
            $normalizedData = $this->serializer->normalize($note);
            $this->addNote($normalizedData);
        }
        return $this->updateDeal($deal);
    }

    public function addDeal(PipedriveDeal $deal){
        $normalizedData = $this->serializer->normalize($deal);
        $response = $this->pipedrive->deals->add($normalizedData);
        if (null !== $response->getData()) {
            return $this->serializer->deserialize(json_encode($response->getData()), PipedriveDeal::class, 'json');
        }
    }

    public function updateDeal(PipedriveDeal $deal){
        $normalizedData = $this->serializer->normalize($deal);
        $response = $this->pipedrive->deals->update($deal->getId(), $normalizedData);
        if (null !== $response->getData()) {
            return $this->serializer->deserialize(json_encode($response->getData()), PipedriveDeal::class, 'json');
        }
    }

    public function saveParticipant($dealId, $personId) {
        /** @var PipedrivePerson $foundPerson */
        return $this->addParticipant($dealId, $personId);
    }

    public function addParticipant($dealId, $personId){
        return $this->pipedrive->deals->addParticipant($dealId, $personId);
    }

    public function getPipeline($num) {
        $response = $this->pipedrive->pipelines->all();
        if (null !== $response->getData()) {
            /** @var PipedriveDeal $deal */
            return $this->serializer->deserialize(json_encode($response->getData()[$num-1]), PipedrivePipeline::class, 'json');
        }
    }

    public function getStage($num, $pipelineId) {
        $response = $this->pipedrive->stages->all(['pipeline_id' => $pipelineId]);
        if (null !== $response->getData()) {
            /** @var PipedriveDeal $deal */
            return $this->serializer->deserialize(json_encode($response->getData()[$num-1]), PipedriveStage::class, 'json');
        }
    }

    public function addNote($normalizedData){
        return $this->pipedrive->notes->add($normalizedData);
    }

    public function apiUpdatePerson($personID, $normalizedData) {
        $response = $this->pipedrive->persons->update($personID, $normalizedData);
        return $response->getData();
    }
}